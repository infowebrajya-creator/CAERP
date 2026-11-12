<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Audit;
use App\Models\Client;
use App\Models\Communication;
use App\Models\ComplianceDeadline;
use App\Models\ComplianceRule;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\FirmSetting;
use App\Models\GstReturn;
use App\Models\GstReturnType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ItrFiling;
use App\Models\Payment;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use App\Models\TdsReturn;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Firm Settings
        $firmSettings = [
            'firm_name' => 'WebRajya Accounting & Advisory',
            'firm_tagline' => 'Accounting, Tax & Assurance Advisors',
            'firm_email' => 'office@webrajya.in',
            'firm_phone' => '+91 22 4001 9900',
            'firm_address' => 'Suite 804, Maker Chambers V, Nariman Point, Mumbai 400021',
            'firm_pan' => 'AABFC7890K',
            'firm_gstin' => '27AABFC7890K1Z4',
            'invoice_prefix' => 'CA-INV',
            'default_cgst_rate' => '9',
            'default_sgst_rate' => '9',
            'default_igst_rate' => '18',
        ];
        foreach ($firmSettings as $k => $v) {
            FirmSetting::setValue($k, $v, 'firm');
        }

        // 2. Staff & Client Users (5 Roles)
        $superAdmin = User::create([
            'name' => 'Rajesh Kulkarni',
            'email' => 'admin@webrajya.in',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPER_ADMIN,
            'employee_code' => 'EMP-001',
            'phone' => '+91 98200 11001',
            'designation' => 'Managing Partner',
            'department' => 'Practice Leadership',
            'avatar_color' => '#1e3a5f',
        ]);

        $partner = User::create([
            'name' => 'Meera Deshmukh',
            'email' => 'partner@webrajya.in',
            'password' => Hash::make('password'),
            'role' => User::ROLE_PARTNER,
            'employee_code' => 'EMP-002',
            'phone' => '+91 98200 11002',
            'designation' => 'Senior Tax Partner',
            'department' => 'Direct Tax & Audit',
            'avatar_color' => '#0284c7',
        ]);

        $manager = User::create([
            'name' => 'Rohan Verma',
            'email' => 'manager@webrajya.in',
            'password' => Hash::make('password'),
            'role' => User::ROLE_MANAGER,
            'employee_code' => 'EMP-003',
            'phone' => '+91 98200 11003',
            'designation' => 'Compliance Manager',
            'department' => 'GST & ROC',
            'avatar_color' => '#0d9488',
        ]);

        $employee = User::create([
            'name' => 'Priya Nair',
            'email' => 'employee@webrajya.in',
            'password' => Hash::make('password'),
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => 'EMP-004',
            'phone' => '+91 98200 11004',
            'designation' => 'Senior Accountant',
            'department' => 'GST & TDS',
            'avatar_color' => '#4f46e5',
        ]);

        $employee2 = User::create([
            'name' => 'Sneha Iyer',
            'email' => 'sneha@webrajya.in',
            'password' => Hash::make('password'),
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => 'EMP-005',
            'phone' => '+91 98200 11005',
            'designation' => 'Audit Associate',
            'department' => 'Assurance',
            'avatar_color' => '#0f766e',
        ]);

        $clientUser1 = User::create([
            'name' => 'Vikram Mehta (Apex Precision)',
            'email' => 'client@apexprecision.in',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CLIENT,
            'phone' => '+91 98211 44550',
            'avatar_color' => '#0d9488',
        ]);

        $clientUser2 = User::create([
            'name' => 'Ananya Kapoor',
            'email' => 'ananya@kapoorholdings.in',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CLIENT,
            'phone' => '+91 98211 44551',
            'avatar_color' => '#0284c7',
        ]);

        // 3. Configurable GST Return Types
        $gstr1 = GstReturnType::create(['code' => 'GSTR-1', 'name' => 'Outward Supplies Return', 'frequency' => 'monthly', 'default_due_day' => 11, 'default_due_month_offset' => 1, 'description' => 'Monthly B2B/B2C sales summary']);
        $gstr3b = GstReturnType::create(['code' => 'GSTR-3B', 'name' => 'Monthly Summary Return', 'frequency' => 'monthly', 'default_due_day' => 20, 'default_due_month_offset' => 1, 'description' => 'Tax liability & ITC summary']);
        $gstr9 = GstReturnType::create(['code' => 'GSTR-9', 'name' => 'Annual GST Return', 'frequency' => 'annual', 'default_due_day' => 31, 'default_due_month_offset' => 9, 'description' => 'Annual consolidated GST return']);
        GstReturnType::create(['code' => 'CMP-08', 'name' => 'Composition Quarterly Statement', 'frequency' => 'quarterly', 'default_due_day' => 18, 'default_due_month_offset' => 1, 'description' => 'Quarterly tax payment for composition dealers']);

        // 4. Configurable Compliance Rules
        $ruleGstr1 = ComplianceRule::create(['name' => 'GSTR-1 Monthly Filing', 'compliance_type' => 'gst', 'frequency' => 'monthly', 'due_day' => 11, 'month_offset' => 1, 'reminder_days_before' => 4, 'description' => 'Configurable monthly outward supplies return']);
        $ruleGstr3b = ComplianceRule::create(['name' => 'GSTR-3B Monthly Tax Payment & Return', 'compliance_type' => 'gst', 'frequency' => 'monthly', 'due_day' => 20, 'month_offset' => 1, 'reminder_days_before' => 5, 'description' => 'Configurable monthly summary return']);
        $ruleTds = ComplianceRule::create(['name' => 'Monthly TDS Challan Deposit', 'compliance_type' => 'tds', 'frequency' => 'monthly', 'due_day' => 7, 'month_offset' => 1, 'reminder_days_before' => 3, 'description' => 'Deposit deducted TDS via Challan 281']);
        ComplianceRule::create(['name' => 'Quarterly Advance Tax Installment', 'compliance_type' => 'advance_tax', 'frequency' => 'quarterly', 'due_day' => 15, 'due_month' => 12, 'month_offset' => 0, 'reminder_days_before' => 7, 'description' => '75% cumulative advance tax']);
        ComplianceRule::create(['name' => 'ROC Annual Filing (AOC-4 / MGT-7)', 'compliance_type' => 'roc', 'frequency' => 'annual', 'due_day' => 30, 'due_month' => 11, 'month_offset' => 0, 'reminder_days_before' => 15, 'description' => 'MCA annual financial & return filing']);

        // 5. Clients
        $c1 = Client::create([
            'client_code' => 'CA-CL-0001',
            'user_id' => $clientUser1->id,
            'assigned_ca_id' => $partner->id,
            'category' => 'business',
            'name' => 'Apex Precision Engineering Pvt Ltd',
            'email' => 'client@apexprecision.in',
            'mobile' => '+91 98211 44550',
            'address' => 'Plot 42, MIDC Bhosari',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411026',
            'pan' => 'AAACA4512M',
            'gstin' => '27AAACA4512M1Z2',
            'tan' => 'PNEA12345B',
            'cin' => 'U28999MH2018PTC312450',
            'business_type' => 'Private Limited Company',
            'industry' => 'Precision Manufacturing',
            'registration_date' => now()->subMonths(14),
            'status' => 'active',
            'is_gst_registered' => true,
            'is_itr_applicable' => true,
            'is_tds_applicable' => true,
            'notes' => 'Key manufacturing retainer client. Monthly GST + Quarterly TDS + Statutory Audit.',
        ]);
        $c1->staff()->attach([$employee->id => ['role_label' => 'GST & TDS Executive'], $employee2->id => ['role_label' => 'Audit Associate']]);

        $c2 = Client::create([
            'client_code' => 'CA-CL-0002',
            'user_id' => $clientUser2->id,
            'assigned_ca_id' => $manager->id,
            'category' => 'individual',
            'name' => 'Ananya Kapoor',
            'email' => 'ananya@kapoorholdings.in',
            'mobile' => '+91 98211 44551',
            'address' => '14B, Pali Hill, Bandra West',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400050',
            'pan' => 'AFKPK7821R',
            'business_type' => 'HNI Individual / Consultant',
            'industry' => 'Advisory & Investments',
            'registration_date' => now()->subMonths(10),
            'status' => 'active',
            'is_gst_registered' => false,
            'is_itr_applicable' => true,
            'is_tds_applicable' => false,
            'notes' => 'Capital gains & foreign asset schedule verification required for AY 2026-27.',
        ]);
        $c2->staff()->attach([$employee->id => ['role_label' => 'ITR Associate']]);

        $c3 = Client::create([
            'client_code' => 'CA-CL-0003',
            'assigned_ca_id' => $partner->id,
            'category' => 'business',
            'name' => 'BlueWave Logistics LLP',
            'email' => 'accounts@bluewavelogistics.in',
            'mobile' => '+91 98211 44552',
            'city' => 'Navi Mumbai',
            'state' => 'Maharashtra',
            'pan' => 'AABFB2345K',
            'gstin' => '27AABFB2345K1Z9',
            'tan' => 'MUMB98765C',
            'business_type' => 'LLP',
            'industry' => 'Freight & Logistics',
            'registration_date' => now()->subMonths(8),
            'status' => 'active',
            'is_gst_registered' => true,
            'is_itr_applicable' => true,
            'is_tds_applicable' => true,
        ]);
        $c3->staff()->attach([$employee->id => ['role_label' => 'GST Executive']]);

        $c4 = Client::create([
            'client_code' => 'CA-CL-0004',
            'assigned_ca_id' => $superAdmin->id,
            'category' => 'business',
            'name' => 'Veritas Cloud Solutions Pvt Ltd',
            'email' => 'finance@veritascloud.io',
            'mobile' => '+91 98211 44553',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pan' => 'AAACV8891L',
            'gstin' => '29AAACV8891L1Z1',
            'tan' => 'BLRV45612D',
            'cin' => 'U72900KA2021PTC148920',
            'business_type' => 'Private Limited Company',
            'industry' => 'SaaS / IT Exports',
            'registration_date' => now()->subMonths(5),
            'status' => 'active',
            'is_gst_registered' => true,
            'is_itr_applicable' => true,
            'is_tds_applicable' => true,
        ]);

        $c5 = Client::create([
            'client_code' => 'CA-CL-0005',
            'assigned_ca_id' => $manager->id,
            'category' => 'business',
            'name' => 'Sahyadri Agro Foods',
            'email' => 'sahayadri.foods@example.in',
            'mobile' => '+91 98211 44554',
            'city' => 'Nashik',
            'state' => 'Maharashtra',
            'pan' => 'ABFPS1234N',
            'gstin' => '27ABFPS1234N1Z8',
            'business_type' => 'Partnership Firm',
            'industry' => 'Food Processing',
            'registration_date' => now()->subMonths(2),
            'status' => 'onboarding',
            'is_gst_registered' => true,
            'is_itr_applicable' => true,
            'is_tds_applicable' => false,
        ]);

        $c6 = Client::create([
            'client_code' => 'CA-CL-0006',
            'assigned_ca_id' => $employee->id,
            'category' => 'individual',
            'name' => 'Dr. Siddharth Rao',
            'email' => 'siddharth.rao@example.in',
            'mobile' => '+91 98211 44555',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pan' => 'ADGPR3319Q',
            'business_type' => 'Medical Professional (44ADA)',
            'industry' => 'Healthcare',
            'registration_date' => now()->subMonths(4),
            'status' => 'active',
            'is_gst_registered' => false,
            'is_itr_applicable' => true,
            'is_tds_applicable' => false,
        ]);

        // 6. GST Returns
        GstReturn::create([
            'client_id' => $c1->id, 'return_type_id' => $gstr1->id, 'return_type_code' => 'GSTR-1',
            'financial_year' => '2026-27', 'tax_period' => 'Oct 2026', 'period_month' => 10, 'period_year' => 2026,
            'due_date' => now()->addDays(2), 'status' => 'in_progress', 'assigned_to' => $employee->id,
            'internal_notes' => 'B2B invoices uploaded; verifying E-way bill summary.',
            'document_checklist' => [
                ['label' => 'Sales Register Excel', 'received' => true],
                ['label' => 'Credit/Debit Notes', 'received' => true],
                ['label' => 'Export LUT Details', 'received' => false],
            ],
        ]);
        GstReturn::create([
            'client_id' => $c1->id, 'return_type_id' => $gstr3b->id, 'return_type_code' => 'GSTR-3B',
            'financial_year' => '2026-27', 'tax_period' => 'Sep 2026', 'period_month' => 9, 'period_year' => 2026,
            'due_date' => now()->subDays(22), 'status' => 'filed', 'arn' => 'AA2710260451290',
            'filing_date' => now()->subDays(23), 'assigned_to' => $employee->id,
        ]);
        GstReturn::create([
            'client_id' => $c3->id, 'return_type_id' => $gstr3b->id, 'return_type_code' => 'GSTR-3B',
            'financial_year' => '2026-27', 'tax_period' => 'Oct 2026', 'period_month' => 10, 'period_year' => 2026,
            'due_date' => now()->addDays(9), 'status' => 'documents_pending', 'assigned_to' => $employee->id,
            'document_checklist' => [
                ['label' => 'Purchase Register for 2B match', 'received' => false],
                ['label' => 'RCM Freight Ledger', 'received' => false],
            ],
        ]);
        GstReturn::create([
            'client_id' => $c4->id, 'return_type_id' => $gstr1->id, 'return_type_code' => 'GSTR-1',
            'financial_year' => '2026-27', 'tax_period' => 'Sep 2026', 'period_month' => 9, 'period_year' => 2026,
            'due_date' => now()->subDays(30), 'status' => 'filed', 'arn' => 'AA2910260187432',
            'filing_date' => now()->subDays(31), 'assigned_to' => $manager->id,
        ]);
        GstReturn::create([
            'client_id' => $c5->id, 'return_type_id' => $gstr9->id, 'return_type_code' => 'GSTR-9',
            'financial_year' => '2025-26', 'tax_period' => 'FY 2025-26', 'period_month' => 12, 'period_year' => 2026,
            'due_date' => now()->subDays(5), 'status' => 'overdue', 'assigned_to' => $employee->id,
        ]);

        // 7. ITR Filings
        ItrFiling::create([
            'client_id' => $c1->id, 'assessment_year' => '2026-27', 'financial_year' => '2025-26',
            'itr_category' => 'Private Limited Company', 'return_type' => 'ITR-6', 'assigned_to' => $partner->id,
            'document_status' => 'partial', 'filing_status' => 'computation_ready', 'due_date' => now()->addDays(18),
            'refund_status' => 'not_applicable',
            'pending_documents_checklist' => [
                ['label' => 'Audited Financials', 'received' => true],
                ['label' => 'Tax Audit 3CD Draft', 'received' => false],
            ],
        ]);
        ItrFiling::create([
            'client_id' => $c2->id, 'assessment_year' => '2026-27', 'financial_year' => '2025-26',
            'itr_category' => 'Individual', 'return_type' => 'ITR-2', 'assigned_to' => $employee->id,
            'document_status' => 'complete', 'filing_status' => 'filed', 'due_date' => now()->subDays(40),
            'filing_date' => now()->subDays(42), 'acknowledgement_number' => '845120938471203',
            'refund_status' => 'pending', 'refund_amount' => 48500,
        ]);
        ItrFiling::create([
            'client_id' => $c6->id, 'assessment_year' => '2026-27', 'financial_year' => '2025-26',
            'itr_category' => 'Individual', 'return_type' => 'ITR-4', 'assigned_to' => $employee->id,
            'document_status' => 'pending', 'filing_status' => 'documents_pending', 'due_date' => now()->addDays(12),
            'refund_status' => 'not_applicable',
        ]);

        // 8. TDS Returns
        TdsReturn::create([
            'client_id' => $c1->id, 'financial_year' => '2026-27', 'quarter' => 'Q2', 'form_type' => '26Q',
            'deduction_amount' => 245000, 'tax_deposited' => 245000, 'challan_number' => '04892', 'bsr_code' => '0510002',
            'challan_date' => now()->subDays(28), 'due_date' => now()->subDays(11), 'filing_date' => now()->subDays(12),
            'acknowledgement_number' => 'PRN884120945', 'status' => 'filed', 'assigned_to' => $employee->id,
        ]);
        TdsReturn::create([
            'client_id' => $c4->id, 'financial_year' => '2026-27', 'quarter' => 'Q2', 'form_type' => '24Q',
            'deduction_amount' => 680000, 'tax_deposited' => 680000, 'challan_number' => '09124', 'bsr_code' => '0220118',
            'challan_date' => now()->subDays(20), 'due_date' => now()->addDays(6), 'status' => 'challan_paid', 'assigned_to' => $employee->id,
        ]);

        // 9. Compliance Deadlines
        ComplianceDeadline::create(['title' => 'Apex Precision GSTR-1 (Oct 2026)', 'compliance_type' => 'gst', 'client_id' => $c1->id, 'assigned_to' => $employee->id, 'compliance_rule_id' => $ruleGstr1->id, 'due_date' => now()->addDays(2), 'reminder_date' => now(), 'status' => 'in_progress', 'priority' => 'high']);
        ComplianceDeadline::create(['title' => 'BlueWave GSTR-3B Return', 'compliance_type' => 'gst', 'client_id' => $c3->id, 'assigned_to' => $employee->id, 'compliance_rule_id' => $ruleGstr3b->id, 'due_date' => now()->addDays(9), 'reminder_date' => now()->addDays(4), 'status' => 'upcoming', 'priority' => 'medium']);
        ComplianceDeadline::create(['title' => 'Monthly TDS Challan Deposit (281)', 'compliance_type' => 'tds', 'client_id' => $c4->id, 'assigned_to' => $employee->id, 'compliance_rule_id' => $ruleTds->id, 'due_date' => now()->addDays(16), 'reminder_date' => now()->addDays(12), 'status' => 'upcoming', 'priority' => 'high']);
        ComplianceDeadline::create(['title' => 'Advance Tax Installment (Q3)', 'compliance_type' => 'advance_tax', 'client_id' => $c1->id, 'assigned_to' => $partner->id, 'due_date' => now()->addDays(24), 'reminder_date' => now()->addDays(18), 'status' => 'upcoming', 'priority' => 'urgent']);
        ComplianceDeadline::create(['title' => 'Sahyadri Agro GSTR-9 Overdue Follow-up', 'compliance_type' => 'gst', 'client_id' => $c5->id, 'assigned_to' => $manager->id, 'due_date' => now()->subDays(4), 'status' => 'overdue', 'priority' => 'urgent']);

        // 10. Documents (with real stored files for working download verification)
        Storage::disk('local')->put('documents/client_1/sample_gst_recon.txt', "Apex Precision GST Reconciliation Sheet\nPeriod: Oct 2026\nVerified by WebRajya ERP\n");
        $doc1 = Document::create([
            'client_id' => $c1->id, 'title' => 'GSTR-2B Reconciliation Summary (Oct 2026)', 'category' => 'gst_documents',
            'status' => 'approved', 'requested_by' => $employee->id, 'uploaded_by' => $clientUser1->id,
            'reviewed_by' => $manager->id, 'reviewed_at' => now()->subDays(1), 'is_shared_with_client' => true,
            'current_version' => 1, 'file_path' => 'documents/client_1/sample_gst_recon.txt',
            'original_filename' => 'Apex_GSTR2B_Oct2026.txt', 'mime_type' => 'text/plain', 'file_size' => 112,
        ]);
        DocumentVersion::create([
            'document_id' => $doc1->id, 'version_number' => 1, 'file_path' => 'documents/client_1/sample_gst_recon.txt',
            'original_filename' => 'Apex_GSTR2B_Oct2026.txt', 'mime_type' => 'text/plain', 'file_size' => 112,
            'uploaded_by' => $clientUser1->id, 'notes' => 'Uploaded via Client Portal',
        ]);

        Document::create([
            'client_id' => $c1->id, 'title' => 'HDFC Current Account Statement (Q2 FY 2026-27)', 'category' => 'bank_statements',
            'status' => 'requested', 'requested_by' => $employee->id, 'due_date' => now()->addDays(3),
            'request_notes' => 'Please upload PDF statement from July to September 2026.', 'is_shared_with_client' => true,
        ]);
        Document::create([
            'client_id' => $c2->id, 'title' => 'Zerodha Capital Gains Tax P&L Statement', 'category' => 'other',
            'status' => 'requested', 'requested_by' => $employee->id, 'due_date' => now()->addDays(5),
            'request_notes' => 'Needed for Schedule CG verification.', 'is_shared_with_client' => true,
        ]);

        // 11. Audits
        $audit = Audit::create([
            'client_id' => $c1->id,
            'title' => 'Statutory & Tax Audit FY 2025-26',
            'audit_type' => 'tax_audit',
            'financial_year' => '2025-26',
            'lead_auditor_id' => $partner->id,
            'start_date' => now()->subDays(20),
            'due_date' => now()->addDays(25),
            'status' => 'in_progress',
            'progress_percent' => 50,
            'checklist' => [
                ['item' => 'Engagement Letter & Independence Sign-off', 'completed' => true, 'remark' => 'Signed by MD'],
                ['item' => 'Trial Balance & GL Scrutiny', 'completed' => true, 'remark' => 'Matched'],
                ['item' => 'Form 3CD Clause-by-Clause Verification', 'completed' => false, 'remark' => 'Clause 44 in review'],
                ['item' => 'Partner Final Sign-off & UDIN Generation', 'completed' => false, 'remark' => ''],
            ],
            'observations' => 'MSME vendor ageing schedule reconciled; minor TDS 194C interest adjustment noted.',
        ]);
        $audit->teamMembers()->attach([$employee2->id => ['role_in_audit' => 'Audit Associate'], $manager->id => ['role_in_audit' => 'Manager Reviewer']]);

        // 12. Tasks across Kanban stages
        $t1 = Task::create([
            'title' => 'Reconcile GSTR-2B vs Purchase Register (Oct)', 'client_id' => $c1->id, 'assigned_to' => $employee->id,
            'created_by' => $manager->id, 'category' => 'gst', 'priority' => 'high', 'status' => 'in_progress',
            'due_date' => now()->addDays(2), 'description' => 'Check supplier non-filers before claiming ITC.',
        ]);
        TaskComment::create(['task_id' => $t1->id, 'user_id' => $employee->id, 'comment' => '94% invoices matched; following up with 2 vendors.']);
        TaskActivity::create(['task_id' => $t1->id, 'user_id' => $manager->id, 'action' => 'created', 'details' => 'Assigned to Priya Nair']);

        Task::create(['title' => 'Prepare Form 3CD Clause 44 Breakup', 'client_id' => $c1->id, 'assigned_to' => $employee2->id, 'created_by' => $partner->id, 'category' => 'audit', 'priority' => 'urgent', 'status' => 'under_review', 'due_date' => now()->addDays(4)]);
        Task::create(['title' => 'Collect Bank Statements from BlueWave LLP', 'client_id' => $c3->id, 'assigned_to' => $employee->id, 'created_by' => $manager->id, 'category' => 'gst', 'priority' => 'medium', 'status' => 'todo', 'due_date' => now()->subDays(1)]);
        Task::create(['title' => 'File ITR-2 & Verify E-Verification', 'client_id' => $c2->id, 'assigned_to' => $employee->id, 'created_by' => $partner->id, 'category' => 'itr', 'priority' => 'medium', 'status' => 'completed', 'due_date' => now()->subDays(40), 'completed_at' => now()->subDays(42)]);

        // 13. Invoices & Payments (Last 6 months for revenue chart + current outstanding)
        $inv1 = Invoice::create([
            'invoice_number' => 'CA-INV-2026-0001', 'client_id' => $c1->id,
            'invoice_date' => now()->subMonths(2)->startOfMonth()->addDays(4), 'due_date' => now()->subMonths(2)->startOfMonth()->addDays(19),
            'tax_mode' => 'cgst_sgst', 'subtotal' => 45000, 'discount_amount' => 0, 'taxable_amount' => 45000,
            'cgst_rate' => 9, 'cgst_amount' => 4050, 'sgst_rate' => 9, 'sgst_amount' => 4050, 'igst_rate' => 0, 'igst_amount' => 0,
            'total_amount' => 53100, 'paid_amount' => 53100, 'balance_amount' => 0, 'status' => 'paid', 'created_by' => $partner->id,
        ]);
        InvoiceItem::create(['invoice_id' => $inv1->id, 'service_description' => 'Quarterly GST & TDS Retainer Fee', 'sac_code' => '998221', 'quantity' => 1, 'unit_price' => 45000, 'amount' => 45000]);
        Payment::create(['invoice_id' => $inv1->id, 'client_id' => $c1->id, 'payment_date' => now()->subMonths(2)->startOfMonth()->addDays(12), 'amount' => 53100, 'payment_mode' => 'bank_transfer', 'reference_number' => 'HDFC-NEFT-89120', 'recorded_by' => $partner->id]);

        $inv2 = Invoice::create([
            'invoice_number' => 'CA-INV-2026-0002', 'client_id' => $c4->id,
            'invoice_date' => now()->subMonths(1)->startOfMonth()->addDays(3), 'due_date' => now()->subMonths(1)->startOfMonth()->addDays(18),
            'tax_mode' => 'igst', 'subtotal' => 60000, 'discount_amount' => 0, 'taxable_amount' => 60000,
            'cgst_rate' => 0, 'cgst_amount' => 0, 'sgst_rate' => 0, 'sgst_amount' => 0, 'igst_rate' => 18, 'igst_amount' => 10800,
            'total_amount' => 70800, 'paid_amount' => 70800, 'balance_amount' => 0, 'status' => 'paid', 'created_by' => $superAdmin->id,
        ]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'service_description' => 'FEMA / Export LUT & Transfer Pricing Advisory', 'sac_code' => '998222', 'quantity' => 1, 'unit_price' => 60000, 'amount' => 60000]);
        Payment::create(['invoice_id' => $inv2->id, 'client_id' => $c4->id, 'payment_date' => now()->subMonths(1)->startOfMonth()->addDays(10), 'amount' => 70800, 'payment_mode' => 'bank_transfer', 'reference_number' => 'ICICI-RTGS-4491', 'recorded_by' => $superAdmin->id]);

        $inv3 = Invoice::create([
            'invoice_number' => 'CA-INV-2026-0003', 'client_id' => $c1->id,
            'invoice_date' => now()->startOfMonth()->addDays(1), 'due_date' => now()->startOfMonth()->addDays(16),
            'tax_mode' => 'cgst_sgst', 'subtotal' => 50000, 'discount_amount' => 0, 'taxable_amount' => 50000,
            'cgst_rate' => 9, 'cgst_amount' => 4500, 'sgst_rate' => 9, 'sgst_amount' => 4500, 'igst_rate' => 0, 'igst_amount' => 0,
            'total_amount' => 59000, 'paid_amount' => 25000, 'balance_amount' => 34000, 'status' => 'partially_paid', 'created_by' => $partner->id,
        ]);
        InvoiceItem::create(['invoice_id' => $inv3->id, 'service_description' => 'Tax Audit Sec 44AB Advance Fee', 'sac_code' => '998221', 'quantity' => 1, 'unit_price' => 50000, 'amount' => 50000]);
        Payment::create(['invoice_id' => $inv3->id, 'client_id' => $c1->id, 'payment_date' => now()->startOfMonth()->addDays(3), 'amount' => 25000, 'payment_mode' => 'upi', 'reference_number' => 'UPI-431987412', 'recorded_by' => $partner->id]);

        $inv4 = Invoice::create([
            'invoice_number' => 'CA-INV-2026-0004', 'client_id' => $c3->id,
            'invoice_date' => now()->subDays(25), 'due_date' => now()->subDays(10),
            'tax_mode' => 'cgst_sgst', 'subtotal' => 20000, 'discount_amount' => 0, 'taxable_amount' => 20000,
            'cgst_rate' => 9, 'cgst_amount' => 1800, 'sgst_rate' => 9, 'sgst_amount' => 1800, 'igst_rate' => 0, 'igst_amount' => 0,
            'total_amount' => 23600, 'paid_amount' => 0, 'balance_amount' => 23600, 'status' => 'overdue', 'created_by' => $manager->id,
        ]);
        InvoiceItem::create(['invoice_id' => $inv4->id, 'service_description' => 'Monthly GST & TDS Compliance Fee', 'sac_code' => '998221', 'quantity' => 1, 'unit_price' => 20000, 'amount' => 20000]);

        // 14. Communications
        Communication::create([
            'client_id' => $c1->id, 'user_id' => $employee->id, 'sender_type' => 'staff', 'channel' => 'portal',
            'subject' => 'Q2 Bank Statements Requested',
            'message' => 'Dear Vikram, please upload your HDFC Current Account statement for Q2 via the Client Portal Documents tab.',
            'status' => 'sent', 'sent_at' => now()->subDays(1),
        ]);

        // 15. Initial Activity Logs
        ActivityLog::create(['user_id' => $superAdmin->id, 'module' => 'clients', 'action' => 'created', 'description' => 'Onboarded Apex Precision Engineering Pvt Ltd']);
        ActivityLog::create(['user_id' => $employee->id, 'module' => 'gst', 'action' => 'filed', 'description' => 'Filed GSTR-3B for Sep 2026 (ARN: AA2710260451290)']);
        ActivityLog::create(['user_id' => $partner->id, 'module' => 'billing', 'action' => 'payment_recorded', 'description' => 'Recorded INR 25,000.00 partial payment on CA-INV-2026-0003']);
    }
}
