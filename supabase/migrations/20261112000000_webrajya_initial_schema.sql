-- ============================================================================
-- WebRajya ERP - Supabase PostgreSQL Schema & Security Migration
-- Version: 1.0.0
-- Generated for: WebRajya Practice Management ERP
-- ============================================================================

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ============================================================================
-- 1. ENUMS & TYPES
-- ============================================================================
DO $$ BEGIN
    CREATE TYPE user_role AS ENUM ('super_admin', 'partner', 'manager', 'employee', 'client');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE client_category AS ENUM ('individual', 'business');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE client_status AS ENUM ('active', 'inactive', 'onboarding');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE filing_status AS ENUM ('not_started', 'documents_pending', 'in_progress', 'filed', 'overdue');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE task_priority AS ENUM ('low', 'medium', 'high', 'urgent');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE task_status AS ENUM ('todo', 'in_progress', 'under_review', 'completed');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE invoice_status AS ENUM ('unpaid', 'partially_paid', 'paid', 'overdue');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE tax_mode AS ENUM ('cgst_sgst', 'igst', 'exempt');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE document_status AS ENUM ('requested', 'uploaded', 'approved', 'rejected');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- ============================================================================
-- 2. USER PROFILES (Linked to auth.users)
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.profiles (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    email TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    role user_role NOT NULL DEFAULT 'employee',
    employee_code VARCHAR(30) UNIQUE,
    phone VARCHAR(20),
    designation VARCHAR(100),
    department VARCHAR(100),
    is_active BOOLEAN NOT NULL DEFAULT true,
    avatar_color VARCHAR(20) DEFAULT '#1e3a5f',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_profiles_role ON public.profiles(role);
CREATE INDEX IF NOT EXISTS idx_profiles_email ON public.profiles(email);

-- Auto-sync profile on signup
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO public.profiles (id, email, name, role)
    VALUES (
        NEW.id,
        NEW.email,
        COALESCE(NEW.raw_user_meta_data->>'name', split_part(NEW.email, '@', 1)),
        COALESCE((NEW.raw_user_meta_data->>'role')::user_role, 'client'::user_role)
    )
    ON CONFLICT (id) DO UPDATE SET
        email = EXCLUDED.email,
        name = COALESCE(EXCLUDED.name, public.profiles.name);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- Helper function: Get Current User Role
CREATE OR REPLACE FUNCTION public.current_user_role()
RETURNS user_role AS $$
    SELECT role FROM public.profiles WHERE id = auth.uid();
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- ============================================================================
-- 3. FIRM SETTINGS & AUDIT LOGS
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.firm_settings (
    id BIGSERIAL PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT,
    group_name VARCHAR(50) NOT NULL DEFAULT 'firm',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.activity_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    action VARCHAR(60) NOT NULL,
    module VARCHAR(60) NOT NULL,
    subject_type VARCHAR(100),
    subject_id BIGINT,
    description TEXT NOT NULL,
    properties JSONB,
    ip_address VARCHAR(45),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_activity_logs_user ON public.activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_module ON public.activity_logs(module);

-- ============================================================================
-- 4. CLIENT MANAGEMENT (CRM)
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.clients (
    id BIGSERIAL PRIMARY KEY,
    client_code VARCHAR(30) UNIQUE NOT NULL,
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL, -- Client Portal login user
    assigned_ca_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL, -- Relationship Manager
    category client_category NOT NULL DEFAULT 'business',
    name TEXT NOT NULL,
    email VARCHAR(255),
    mobile VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(15),
    pan VARCHAR(20),
    gstin VARCHAR(20),
    tan VARCHAR(20),
    cin VARCHAR(30),
    business_type VARCHAR(100),
    industry VARCHAR(100),
    registration_date DATE,
    status client_status NOT NULL DEFAULT 'active',
    is_gst_registered BOOLEAN NOT NULL DEFAULT false,
    is_itr_applicable BOOLEAN NOT NULL DEFAULT true,
    is_tds_applicable BOOLEAN NOT NULL DEFAULT false,
    notes TEXT,
    deleted_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_clients_status ON public.clients(status);
CREATE INDEX IF NOT EXISTS idx_clients_pan ON public.clients(pan);
CREATE INDEX IF NOT EXISTS idx_clients_gstin ON public.clients(gstin);
CREATE INDEX IF NOT EXISTS idx_clients_user_id ON public.clients(user_id);
CREATE INDEX IF NOT EXISTS idx_clients_assigned_ca ON public.clients(assigned_ca_id);

-- Client Secondary Staff Assignments
CREATE TABLE IF NOT EXISTS public.client_staff (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    role_label VARCHAR(60) DEFAULT 'Assigned Staff',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(client_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_client_staff_user ON public.client_staff(user_id);

-- Helper function: Can Staff Access Client?
CREATE OR REPLACE FUNCTION public.can_access_client(c_id BIGINT)
RETURNS BOOLEAN AS $$
DECLARE
    u_role user_role;
    u_id UUID := auth.uid();
BEGIN
    SELECT role INTO u_role FROM public.profiles WHERE id = u_id;
    IF u_role IN ('super_admin', 'partner') THEN
        RETURN true;
    ELSIF u_role IN ('manager', 'employee') THEN
        RETURN EXISTS (
            SELECT 1 FROM public.clients c
            WHERE c.id = c_id AND (
                c.assigned_ca_id = u_id
                OR EXISTS (SELECT 1 FROM public.client_staff cs WHERE cs.client_id = c_id AND cs.user_id = u_id)
            )
        );
    ELSIF u_role = 'client' THEN
        RETURN EXISTS (
            SELECT 1 FROM public.clients c WHERE c.id = c_id AND c.user_id = u_id
        );
    END IF;
    RETURN false;
END;
$$ LANGUAGE plpgsql STABLE SECURITY DEFINER;

-- ============================================================================
-- 5. COMPLIANCE MODULES (GST, ITR, TDS)
-- ============================================================================

-- GST Return Types
CREATE TABLE IF NOT EXISTS public.gst_return_types (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(30) UNIQUE NOT NULL,
    name TEXT NOT NULL,
    frequency VARCHAR(20) NOT NULL DEFAULT 'monthly',
    default_due_day SMALLINT NOT NULL DEFAULT 20,
    default_due_month_offset SMALLINT NOT NULL DEFAULT 1,
    description TEXT,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- GST Returns
CREATE TABLE IF NOT EXISTS public.gst_returns (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    return_type_id BIGINT REFERENCES public.gst_return_types(id) ON DELETE SET NULL,
    return_type_code VARCHAR(30) NOT NULL,
    financial_year VARCHAR(20) NOT NULL,
    tax_period VARCHAR(30) NOT NULL,
    period_month SMALLINT,
    period_year SMALLINT,
    due_date DATE NOT NULL,
    status filing_status NOT NULL DEFAULT 'not_started',
    arn VARCHAR(60),
    filing_date DATE,
    assigned_to UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    internal_notes TEXT,
    document_checklist JSONB DEFAULT '[]'::JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_gst_returns_client ON public.gst_returns(client_id);
CREATE INDEX IF NOT EXISTS idx_gst_returns_due_date ON public.gst_returns(due_date);
CREATE INDEX IF NOT EXISTS idx_gst_returns_status ON public.gst_returns(status);

-- ITR Filings
CREATE TABLE IF NOT EXISTS public.itr_filings (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    assessment_year VARCHAR(20) NOT NULL,
    financial_year VARCHAR(20) NOT NULL,
    itr_category VARCHAR(50) DEFAULT 'Individual',
    return_type VARCHAR(30) NOT NULL DEFAULT 'ITR-1',
    assigned_to UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    document_status VARCHAR(30) DEFAULT 'pending',
    filing_status filing_status NOT NULL DEFAULT 'not_started',
    due_date DATE,
    filing_date DATE,
    acknowledgement_number VARCHAR(60),
    refund_status VARCHAR(30) DEFAULT 'not_applicable',
    refund_amount NUMERIC(12, 2) DEFAULT 0,
    pending_documents_checklist JSONB DEFAULT '[]'::JSONB,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_itr_client ON public.itr_filings(client_id);
CREATE INDEX IF NOT EXISTS idx_itr_ay ON public.itr_filings(assessment_year);
CREATE INDEX IF NOT EXISTS idx_itr_status ON public.itr_filings(filing_status);

-- TDS Returns
CREATE TABLE IF NOT EXISTS public.tds_returns (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    financial_year VARCHAR(20) NOT NULL,
    quarter VARCHAR(10) NOT NULL, -- Q1, Q2, Q3, Q4
    form_type VARCHAR(20) NOT NULL, -- 24Q, 26Q, 27Q, 27EQ
    deduction_amount NUMERIC(14, 2) DEFAULT 0,
    tax_deposited NUMERIC(14, 2) DEFAULT 0,
    challan_number VARCHAR(50),
    bsr_code VARCHAR(20),
    challan_date DATE,
    due_date DATE NOT NULL,
    filing_date DATE,
    token_number VARCHAR(60),
    status filing_status NOT NULL DEFAULT 'not_started',
    assigned_to UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_tds_client ON public.tds_returns(client_id);
CREATE INDEX IF NOT EXISTS idx_tds_status ON public.tds_returns(status);

-- ============================================================================
-- 6. COMPLIANCE CALENDAR & RULES
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.compliance_rules (
    id BIGSERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    compliance_type VARCHAR(30) NOT NULL,
    frequency VARCHAR(20) DEFAULT 'monthly',
    due_day SMALLINT NOT NULL,
    due_month SMALLINT,
    month_offset SMALLINT DEFAULT 0,
    reminder_days_before SMALLINT DEFAULT 5,
    description TEXT,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.compliance_deadlines (
    id BIGSERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    compliance_type VARCHAR(30) NOT NULL,
    client_id BIGINT REFERENCES public.clients(id) ON DELETE CASCADE,
    assigned_to UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    compliance_rule_id BIGINT REFERENCES public.compliance_rules(id) ON DELETE SET NULL,
    due_date DATE NOT NULL,
    reminder_date DATE,
    status VARCHAR(30) DEFAULT 'upcoming', -- upcoming, in_progress, completed, overdue
    priority task_priority DEFAULT 'medium',
    description TEXT,
    completed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_deadlines_due_date ON public.compliance_deadlines(due_date);
CREATE INDEX IF NOT EXISTS idx_deadlines_client ON public.compliance_deadlines(client_id);
CREATE INDEX IF NOT EXISTS idx_deadlines_status ON public.compliance_deadlines(status);

-- ============================================================================
-- 7. DOCUMENT MANAGEMENT & VAULT
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.documents (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    category VARCHAR(40) NOT NULL DEFAULT 'other',
    status document_status NOT NULL DEFAULT 'requested',
    requested_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    uploaded_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    reviewed_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    reviewed_at TIMESTAMPTZ,
    rejection_reason TEXT,
    request_notes TEXT,
    due_date DATE,
    is_shared_with_client BOOLEAN NOT NULL DEFAULT true,
    current_version INT NOT NULL DEFAULT 0,
    file_path TEXT,
    original_filename TEXT,
    mime_type VARCHAR(120),
    file_size BIGINT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_documents_client ON public.documents(client_id);
CREATE INDEX IF NOT EXISTS idx_documents_status ON public.documents(status);

CREATE TABLE IF NOT EXISTS public.document_versions (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES public.documents(id) ON DELETE CASCADE,
    version_number INT NOT NULL,
    file_path TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    mime_type VARCHAR(120),
    file_size BIGINT,
    uploaded_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_document_versions_doc ON public.document_versions(document_id);

-- ============================================================================
-- 8. AUDIT MANAGEMENT
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.audits (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    audit_type VARCHAR(50) NOT NULL, -- Tax Audit (44AB), Statutory Audit, Internal Audit, etc.
    title TEXT NOT NULL,
    financial_year VARCHAR(20) NOT NULL,
    lead_auditor_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    start_date DATE,
    target_completion_date DATE,
    actual_completion_date DATE,
    status VARCHAR(30) NOT NULL DEFAULT 'planned', -- planned, in_progress, under_review, approved, completed
    progress_percent SMALLINT NOT NULL DEFAULT 0,
    checklist JSONB DEFAULT '[]'::JSONB,
    observations TEXT,
    review_notes TEXT,
    approved_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    approved_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.audit_team_members (
    id BIGSERIAL PRIMARY KEY,
    audit_id BIGINT NOT NULL REFERENCES public.audits(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    role_in_audit VARCHAR(60) DEFAULT 'Audit Member',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(audit_id, user_id)
);

CREATE TABLE IF NOT EXISTS public.audit_working_papers (
    id BIGSERIAL PRIMARY KEY,
    audit_id BIGINT NOT NULL REFERENCES public.audits(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    file_path TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    mime_type VARCHAR(120),
    file_size BIGINT,
    uploaded_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================================
-- 9. TASK & WORKFLOW MANAGEMENT (KANBAN)
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.tasks (
    id BIGSERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    client_id BIGINT REFERENCES public.clients(id) ON DELETE SET NULL,
    assigned_to UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    created_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    category VARCHAR(30) DEFAULT 'general',
    priority task_priority NOT NULL DEFAULT 'medium',
    status task_status NOT NULL DEFAULT 'todo',
    due_date DATE,
    completed_at TIMESTAMPTZ,
    approved_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    approved_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_tasks_client ON public.tasks(client_id);
CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON public.tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON public.tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_due_date ON public.tasks(due_date);

CREATE TABLE IF NOT EXISTS public.task_comments (
    id BIGSERIAL PRIMARY KEY,
    task_id BIGINT NOT NULL REFERENCES public.tasks(id) ON DELETE CASCADE,
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    comment TEXT NOT NULL,
    is_internal BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.task_attachments (
    id BIGSERIAL PRIMARY KEY,
    task_id BIGINT NOT NULL REFERENCES public.tasks(id) ON DELETE CASCADE,
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    file_path TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    mime_type VARCHAR(120),
    file_size BIGINT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.task_activities (
    id BIGSERIAL PRIMARY KEY,
    task_id BIGINT NOT NULL REFERENCES public.tasks(id) ON DELETE CASCADE,
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    action VARCHAR(60) NOT NULL,
    details TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================================
-- 10. BILLING, INVOICES & PAYMENTS
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.invoices (
    id BIGSERIAL PRIMARY KEY,
    invoice_number VARCHAR(40) UNIQUE NOT NULL,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    tax_mode tax_mode NOT NULL DEFAULT 'cgst_sgst',
    subtotal NUMERIC(14, 2) NOT NULL DEFAULT 0,
    discount_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    taxable_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    cgst_rate NUMERIC(5, 2) NOT NULL DEFAULT 9.00,
    cgst_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    sgst_rate NUMERIC(5, 2) NOT NULL DEFAULT 9.00,
    sgst_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    igst_rate NUMERIC(5, 2) NOT NULL DEFAULT 18.00,
    igst_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    total_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    paid_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    balance_amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    status invoice_status NOT NULL DEFAULT 'unpaid',
    notes TEXT,
    created_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_invoices_client ON public.invoices(client_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON public.invoices(status);
CREATE INDEX IF NOT EXISTS idx_invoices_due_date ON public.invoices(due_date);

CREATE TABLE IF NOT EXISTS public.invoice_items (
    id BIGSERIAL PRIMARY KEY,
    invoice_id BIGINT NOT NULL REFERENCES public.invoices(id) ON DELETE CASCADE,
    service_description TEXT NOT NULL,
    sac_code VARCHAR(20),
    quantity NUMERIC(10, 2) NOT NULL DEFAULT 1,
    unit_price NUMERIC(14, 2) NOT NULL DEFAULT 0,
    amount NUMERIC(14, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.payments (
    id BIGSERIAL PRIMARY KEY,
    invoice_id BIGINT NOT NULL REFERENCES public.invoices(id) ON DELETE CASCADE,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    payment_date DATE NOT NULL,
    amount NUMERIC(14, 2) NOT NULL,
    payment_mode VARCHAR(30) DEFAULT 'bank_transfer',
    reference_number VARCHAR(80),
    notes TEXT,
    recorded_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_payments_invoice ON public.payments(invoice_id);
CREATE INDEX IF NOT EXISTS idx_payments_client ON public.payments(client_id);

-- Auto-recalculate invoice balances on payment insert/update/delete
CREATE OR REPLACE FUNCTION public.recalculate_invoice_balance()
RETURNS TRIGGER AS $$
DECLARE
    target_invoice_id BIGINT;
    v_total_paid NUMERIC(14, 2);
    v_total NUMERIC(14, 2);
    v_balance NUMERIC(14, 2);
    v_status invoice_status;
BEGIN
    target_invoice_id := COALESCE(NEW.invoice_id, OLD.invoice_id);
    
    SELECT COALESCE(SUM(amount), 0) INTO v_total_paid
    FROM public.payments
    WHERE invoice_id = target_invoice_id;

    SELECT total_amount INTO v_total
    FROM public.invoices
    WHERE id = target_invoice_id;

    v_balance := GREATEST(v_total - v_total_paid, 0);

    IF v_total_paid <= 0 THEN
        v_status := 'unpaid'::invoice_status;
    ELSIF v_balance = 0 THEN
        v_status := 'paid'::invoice_status;
    ELSE
        v_status := 'partially_paid'::invoice_status;
    END IF;

    UPDATE public.invoices
    SET paid_amount = v_total_paid,
        balance_amount = v_balance,
        status = v_status,
        updated_at = NOW()
    WHERE id = target_invoice_id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_payment_changed ON public.payments;
CREATE TRIGGER on_payment_changed
    AFTER INSERT OR UPDATE OR DELETE ON public.payments
    FOR EACH ROW EXECUTE FUNCTION public.recalculate_invoice_balance();

-- ============================================================================
-- 11. CLIENT COMMUNICATIONS & MESSAGING
-- ============================================================================
CREATE TABLE IF NOT EXISTS public.communications (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES public.clients(id) ON DELETE CASCADE,
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    sender_type VARCHAR(20) NOT NULL DEFAULT 'staff', -- staff, client
    channel VARCHAR(30) NOT NULL DEFAULT 'portal', -- portal, email, whatsapp, call, meeting
    subject TEXT,
    message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'logged', -- logged, sent, queued, failed, not_configured
    meta JSONB,
    sent_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_communications_client ON public.communications(client_id);
CREATE INDEX IF NOT EXISTS idx_communications_channel ON public.communications(channel);

-- ============================================================================
-- 12. ROW LEVEL SECURITY (RLS) POLICIES
-- ============================================================================

-- Enable RLS on all tables
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.firm_settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.activity_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.clients ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.client_staff ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.gst_return_types ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.gst_returns ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.itr_filings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tds_returns ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.compliance_rules ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.compliance_deadlines ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.document_versions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.audits ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.audit_team_members ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.audit_working_papers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tasks ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.task_comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.task_attachments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.task_activities ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invoice_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.communications ENABLE ROW LEVEL SECURITY;

-- PROFILES
CREATE POLICY "Users can view their own profile" ON public.profiles FOR SELECT USING (id = auth.uid());
CREATE POLICY "Staff can view staff profiles" ON public.profiles FOR SELECT USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);
CREATE POLICY "Admins and Partners can update profiles" ON public.profiles FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner')
);

-- FIRM SETTINGS
CREATE POLICY "Anyone authenticated can view firm settings" ON public.firm_settings FOR SELECT TO authenticated USING (true);
CREATE POLICY "Partners and Admins can manage firm settings" ON public.firm_settings FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner')
);

-- CLIENTS
CREATE POLICY "Staff and Client selective view on clients" ON public.clients FOR SELECT USING (
    public.can_access_client(id)
);
CREATE POLICY "Staff can insert/update clients" ON public.clients FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);

-- CLIENT STAFF
CREATE POLICY "Staff can view client staff" ON public.client_staff FOR SELECT USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);
CREATE POLICY "Managers and above can assign client staff" ON public.client_staff FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager')
);

-- GST / ITR / TDS RETURNS
CREATE POLICY "Client and assigned staff view GST returns" ON public.gst_returns FOR SELECT USING (
    public.can_access_client(client_id)
);
CREATE POLICY "Staff manage GST returns" ON public.gst_returns FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);

CREATE POLICY "Client and assigned staff view ITR filings" ON public.itr_filings FOR SELECT USING (
    public.can_access_client(client_id)
);
CREATE POLICY "Staff manage ITR filings" ON public.itr_filings FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);

CREATE POLICY "Client and assigned staff view TDS returns" ON public.tds_returns FOR SELECT USING (
    public.can_access_client(client_id)
);
CREATE POLICY "Staff manage TDS returns" ON public.tds_returns FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);

-- DOCUMENTS & VERSIONS
CREATE POLICY "View documents with client isolation" ON public.documents FOR SELECT USING (
    public.can_access_client(client_id) AND (
        (SELECT role FROM public.profiles WHERE id = auth.uid()) != 'client'
        OR is_shared_with_client = true
    )
);
CREATE POLICY "Clients can upload documents" ON public.documents FOR INSERT WITH CHECK (
    public.can_access_client(client_id)
);
CREATE POLICY "Staff manage documents" ON public.documents FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);

CREATE POLICY "View document versions" ON public.document_versions FOR SELECT USING (
    EXISTS (SELECT 1 FROM public.documents d WHERE d.id = document_id AND public.can_access_client(d.client_id))
);
CREATE POLICY "Insert document versions" ON public.document_versions FOR INSERT WITH CHECK (
    EXISTS (SELECT 1 FROM public.documents d WHERE d.id = document_id AND public.can_access_client(d.client_id))
);

-- INVOICES & PAYMENTS
CREATE POLICY "View invoices" ON public.invoices FOR SELECT USING (
    public.can_access_client(client_id)
);
CREATE POLICY "Managers and above manage invoices" ON public.invoices FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager')
);

CREATE POLICY "View invoice items" ON public.invoice_items FOR SELECT USING (
    EXISTS (SELECT 1 FROM public.invoices i WHERE i.id = invoice_id AND public.can_access_client(i.client_id))
);
CREATE POLICY "Manage invoice items" ON public.invoice_items FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager')
);

CREATE POLICY "View payments" ON public.payments FOR SELECT USING (
    public.can_access_client(client_id)
);
CREATE POLICY "Manage payments" ON public.payments FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager')
);

-- COMMUNICATIONS
CREATE POLICY "View communications" ON public.communications FOR SELECT USING (
    public.can_access_client(client_id)
);
CREATE POLICY "Send communications" ON public.communications FOR INSERT WITH CHECK (
    public.can_access_client(client_id)
);

-- TASKS
CREATE POLICY "Staff view tasks" ON public.tasks FOR SELECT USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);
CREATE POLICY "Staff manage tasks" ON public.tasks FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);

-- AUDITS
CREATE POLICY "Staff view audits" ON public.audits FOR SELECT USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);
CREATE POLICY "Staff manage audits" ON public.audits FOR ALL USING (
    (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
);

-- ============================================================================
-- 13. STORAGE BUCKETS SETUP (Execute in Supabase Storage SQL)
-- ============================================================================
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES 
    ('client-documents', 'client-documents', false, 52428800, ARRAY['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/plain', 'application/zip']),
    ('audit-working-papers', 'audit-working-papers', false, 52428800, ARRAY['application/pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'application/zip']),
    ('task-attachments', 'task-attachments', false, 26214400, NULL),
    ('brand-assets', 'brand-assets', true, 5242880, ARRAY['image/png', 'image/jpeg', 'image/svg+xml', 'image/x-icon'])
ON CONFLICT (id) DO NOTHING;

-- Storage RLS: Authenticated users can download documents they have access to
CREATE POLICY "Authenticated users can read authorized client documents"
ON storage.objects FOR SELECT TO authenticated
USING (
    bucket_id = 'client-documents' AND (
        (SELECT role FROM public.profiles WHERE id = auth.uid()) IN ('super_admin', 'partner', 'manager', 'employee')
        OR (storage.foldername(name))[1] = auth.uid()::text
    )
);

CREATE POLICY "Authenticated users can upload client documents"
ON storage.objects FOR INSERT TO authenticated
WITH CHECK (
    bucket_id = 'client-documents'
);
