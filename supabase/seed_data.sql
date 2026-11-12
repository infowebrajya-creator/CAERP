-- ============================================================================
-- WebRajya ERP - Initial Supabase Seed Data
-- ============================================================================

BEGIN;

-- 1. Firm Settings
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('firm_name', 'WebRajya Accounting & Advisory', 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('firm_tagline', 'Accounting, Tax & Assurance Advisors', 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('firm_email', 'office@webrajya.in', 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('firm_phone', '+91 22 4001 9900', 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('firm_address', 'Suite 804, Maker Chambers V, Nariman Point, Mumbai 400021', 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('firm_pan', 'AABFC7890K', 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('firm_gstin', '27AABFC7890K1Z4', 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('invoice_prefix', 'CA-INV', 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('default_cgst_rate', 9, 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('default_sgst_rate', 9, 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
INSERT INTO public.firm_settings (key, value, group_name) VALUES ('default_igst_rate', 18, 'firm') ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;

-- 2. GST Return Types
INSERT INTO public.gst_return_types (id, code, name, frequency, default_due_day, default_due_month_offset, description, is_active) VALUES (1, 'GSTR-1', 'Outward Supplies Return', 'monthly', 11, 1, 'Monthly B2B/B2C sales summary', true) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.gst_return_types (id, code, name, frequency, default_due_day, default_due_month_offset, description, is_active) VALUES (2, 'GSTR-3B', 'Monthly Summary Return', 'monthly', 20, 1, 'Tax liability & ITC summary', true) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.gst_return_types (id, code, name, frequency, default_due_day, default_due_month_offset, description, is_active) VALUES (3, 'GSTR-9', 'Annual GST Return', 'annual', 31, 9, 'Annual consolidated GST return', true) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.gst_return_types (id, code, name, frequency, default_due_day, default_due_month_offset, description, is_active) VALUES (4, 'CMP-08', 'Composition Quarterly Statement', 'quarterly', 18, 1, 'Quarterly tax payment for composition dealers', true) ON CONFLICT (id) DO NOTHING;

-- 3. Compliance Rules
INSERT INTO public.compliance_rules (id, name, compliance_type, frequency, due_day, due_month, month_offset, reminder_days_before, description, is_active) VALUES (1, 'GSTR-1 Monthly Filing', 'gst', 'monthly', 11, NULL, 1, 4, 'Configurable monthly outward supplies return', true) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.compliance_rules (id, name, compliance_type, frequency, due_day, due_month, month_offset, reminder_days_before, description, is_active) VALUES (2, 'GSTR-3B Monthly Tax Payment & Return', 'gst', 'monthly', 20, NULL, 1, 5, 'Configurable monthly summary return', true) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.compliance_rules (id, name, compliance_type, frequency, due_day, due_month, month_offset, reminder_days_before, description, is_active) VALUES (3, 'Monthly TDS Challan Deposit', 'tds', 'monthly', 7, NULL, 1, 3, 'Deposit deducted TDS via Challan 281', true) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.compliance_rules (id, name, compliance_type, frequency, due_day, due_month, month_offset, reminder_days_before, description, is_active) VALUES (4, 'Quarterly Advance Tax Installment', 'advance_tax', 'quarterly', 15, 12, 0, 7, '75% cumulative advance tax', true) ON CONFLICT (id) DO NOTHING;
INSERT INTO public.compliance_rules (id, name, compliance_type, frequency, due_day, due_month, month_offset, reminder_days_before, description, is_active) VALUES (5, 'ROC Annual Filing (AOC-4 / MGT-7)', 'roc', 'annual', 30, 11, 0, 15, 'MCA annual financial & return filing', true) ON CONFLICT (id) DO NOTHING;

COMMIT;
