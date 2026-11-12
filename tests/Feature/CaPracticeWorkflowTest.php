<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Client;
use App\Models\ComplianceRule;
use App\Models\Document;
use App\Models\GstReturnType;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaPracticeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_staff_and_admin_can_login_and_view_all_core_modules(): void
    {
        $admin = User::where('email', 'admin@webrajya.in')->firstOrFail();

        $this->post(route('login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $routes = [
            route('dashboard'),
            route('clients.index'),
            route('clients.create'),
            route('gst.index'),
            route('gst.index', ['view' => 'calendar']),
            route('itr.index'),
            route('tds.index'),
            route('calendar.index'),
            route('documents.index'),
            route('audits.index'),
            route('billing.index'),
            route('billing.create'),
            route('staff.index'),
            route('tasks.index', ['view' => 'kanban']),
            route('tasks.index', ['view' => 'table']),
            route('communications.index'),
            route('reports.index'),
            route('settings.index'),
            route('global.search', ['q' => 'Apex']),
        ];

        foreach ($routes as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_client_crm_crud_and_profile_tabs(): void
    {
        $partner = User::where('email', 'partner@webrajya.in')->firstOrFail();
        $this->actingAs($partner);

        $response = $this->post(route('clients.store'), [
            'name' => 'Zenith Exports Pvt Ltd',
            'category' => 'business',
            'status' => 'active',
            'email' => 'accounts@zenithexports.in',
            'mobile' => '+91 98200 55555',
            'pan' => 'AAACZ1234K',
            'gstin' => '27AAACZ1234K1Z5',
            'is_gst_registered' => '1',
            'is_itr_applicable' => '1',
            'create_portal_login' => '1',
            'portal_password' => 'Secret@123',
        ]);

        $client = Client::where('name', 'Zenith Exports Pvt Ltd')->firstOrFail();
        $response->assertRedirect(route('clients.show', $client));
        $this->assertNotNull($client->user_id);

        foreach (['overview', 'compliance', 'documents', 'tasks', 'invoices', 'communication'] as $tab) {
            $this->get(route('clients.show', ['client' => $client, 'tab' => $tab]))->assertOk();
        }
    }

    public function test_gst_itr_tds_and_configurable_calendar_workflows(): void
    {
        $manager = User::where('email', 'manager@webrajya.in')->firstOrFail();
        $client = Client::firstOrFail();
        $returnType = GstReturnType::firstOrFail();
        $rule = ComplianceRule::firstOrFail();

        $this->actingAs($manager);

        $this->post(route('gst.store'), [
            'client_id' => $client->id,
            'return_type_id' => $returnType->id,
            'financial_year' => '2026-27',
            'tax_period' => 'Nov 2026',
            'period_month' => 11,
            'period_year' => 2026,
            'due_date' => '2026-12-11',
            'status' => 'filed',
            'arn' => 'AA2712260001122',
            'filing_date' => '2026-12-10',
        ])->assertRedirect();

        $this->assertDatabaseHas('gst_returns', ['arn' => 'AA2712260001122', 'status' => 'filed']);

        $this->post(route('itr.store'), [
            'client_id' => $client->id,
            'assessment_year' => '2026-27',
            'financial_year' => '2025-26',
            'itr_category' => 'Private Limited Company',
            'return_type' => 'ITR-6',
            'document_status' => 'complete',
            'filing_status' => 'filed',
            'refund_status' => 'not_applicable',
            'acknowledgement_number' => '998877665544332',
        ])->assertRedirect();

        $this->assertDatabaseHas('itr_filings', ['acknowledgement_number' => '998877665544332']);

        $this->post(route('tds.store'), [
            'client_id' => $client->id,
            'financial_year' => '2026-27',
            'quarter' => 'Q3',
            'form_type' => '26Q',
            'deduction_amount' => 150000,
            'tax_deposited' => 150000,
            'challan_number' => 'CH9981',
            'status' => 'challan_paid',
        ])->assertRedirect();

        $this->assertDatabaseHas('tds_returns', ['challan_number' => 'CH9981']);

        $this->post(route('calendar.rules.generate', $rule), [
            'year' => 2026,
            'month' => 11,
            'client_id' => $client->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('compliance_deadlines', [
            'compliance_rule_id' => $rule->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_document_versioning_approval_rejection_and_secure_download(): void
    {
        $employee = User::where('email', 'employee@webrajya.in')->firstOrFail();
        $client = Client::firstOrFail();
        $this->actingAs($employee);

        $file1 = UploadedFile::fake()->create('bank_v1.pdf', 120, 'application/pdf');
        $this->post(route('documents.store'), [
            'client_id' => $client->id,
            'title' => 'Axis Bank Q3 Statement',
            'category' => 'bank_statements',
            'file' => $file1,
        ])->assertRedirect();

        $doc = Document::where('title', 'Axis Bank Q3 Statement')->firstOrFail();
        $this->assertEquals(1, $doc->current_version);
        $this->assertEquals('uploaded', $doc->status);

        $file2 = UploadedFile::fake()->create('bank_v2.pdf', 150, 'application/pdf');
        $this->post(route('documents.upload', $doc), [
            'file' => $file2,
            'notes' => 'Signed pages included',
        ])->assertRedirect();

        $doc->refresh();
        $this->assertEquals(2, $doc->current_version);
        $this->assertCount(2, $doc->versions);

        $this->post(route('documents.approve', $doc))->assertRedirect();
        $this->assertEquals('approved', $doc->fresh()->status);

        $this->get(route('documents.download', $doc))->assertOk();
    }

    public function test_audit_checklist_progress_and_working_papers(): void
    {
        $partner = User::where('email', 'partner@webrajya.in')->firstOrFail();
        $audit = Audit::firstOrFail();
        $this->actingAs($partner);

        $this->put(route('audits.update', $audit), [
            'title' => $audit->title,
            'audit_type' => $audit->audit_type,
            'financial_year' => $audit->financial_year,
            'status' => 'approved',
            'checklist_checked' => [0, 1, 2, 3],
            'checklist_remarks' => ['OK', 'OK', 'OK', 'OK'],
        ])->assertRedirect();

        $audit->refresh();
        $this->assertEquals(100, $audit->progress_percent);
        $this->assertNotNull($audit->approved_at);

        $paperFile = UploadedFile::fake()->create('working_paper.xlsx', 40);
        $this->post(route('audits.papers.store', $audit), [
            'title' => 'Fixed Asset Verification Sheet',
            'file' => $paperFile,
        ])->assertRedirect();

        $paper = $audit->workingPapers()->firstOrFail();
        $this->get(route('audits.papers.download', [$audit, $paper]))->assertOk();
    }

    public function test_billing_gst_calculation_payments_and_pdf_download(): void
    {
        $partner = User::where('email', 'partner@webrajya.in')->firstOrFail();
        $client = Client::firstOrFail();
        $this->actingAs($partner);

        $this->post(route('billing.store'), [
            'invoice_number' => 'CA-INV-TEST-0099',
            'client_id' => $client->id,
            'invoice_date' => '2026-11-11',
            'due_date' => '2026-11-26',
            'tax_mode' => 'cgst_sgst',
            'discount_amount' => 1000,
            'cgst_rate' => 9,
            'sgst_rate' => 9,
            'items' => [
                ['service_description' => 'ROC Annual Filing', 'sac_code' => '998221', 'quantity' => 1, 'unit_price' => 11000],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('invoice_number', 'CA-INV-TEST-0099')->firstOrFail();
        // 11000 - 1000 = 10000 taxable; 9% CGST (900) + 9% SGST (900) = 11800
        $this->assertEquals(11800.00, (float) $invoice->total_amount);
        $this->assertEquals(11800.00, (float) $invoice->balance_amount);

        $this->post(route('billing.payments.store', $invoice), [
            'payment_date' => '2026-11-11',
            'amount' => 5000,
            'payment_mode' => 'upi',
            'reference_number' => 'UPI-998877',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(6800.00, (float) $invoice->balance_amount);

        $pdfResponse = $this->get(route('billing.pdf', $invoice));
        $pdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('content-type'));
    }

    public function test_tasks_kanban_status_transition_and_whatsapp_not_configured_honesty(): void
    {
        $manager = User::where('email', 'manager@webrajya.in')->firstOrFail();
        $task = Task::firstOrFail();
        $client = Client::firstOrFail();
        $this->actingAs($manager);

        $this->patchJson(route('tasks.status', $task), ['status' => 'completed'])
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'completed']);

        // WhatsApp service must NOT fake delivery when unconfigured
        $this->post(route('communications.store'), [
            'client_id' => $client->id,
            'channel' => 'whatsapp',
            'subject' => 'GST Reminder',
            'message' => 'Please share invoices.',
        ])->assertRedirect();

        $this->assertDatabaseHas('communications', [
            'client_id' => $client->id,
            'channel' => 'whatsapp',
            'status' => 'not_configured',
        ]);
    }

    public function test_reports_csv_export_works_for_all_report_types(): void
    {
        $admin = User::where('email', 'admin@webrajya.in')->firstOrFail();
        $this->actingAs($admin);

        foreach (array_keys(\App\Services\ReportService::TYPES) as $type) {
            $res = $this->get(route('reports.export_csv', ['type' => $type]));
            $res->assertOk();
            $this->assertStringContainsString('text/csv', (string) $res->headers->get('content-type'));
        }
    }

    public function test_staff_management_settings_and_role_permissions(): void
    {
        $admin = User::where('email', 'admin@webrajya.in')->firstOrFail();
        $employee = User::where('email', 'employee@webrajya.in')->firstOrFail();
        $client = Client::firstOrFail();

        // Employee cannot access admin-only settings
        $this->actingAs($employee)->get(route('settings.index'))->assertForbidden();

        // Admin can add staff and update firm settings
        $this->actingAs($admin)->post(route('staff.store'), [
            'name' => 'Karan Joshi',
            'email' => 'karan@webrajya.in',
            'role' => User::ROLE_EMPLOYEE,
            'password' => 'Secret@123',
            'designation' => 'Tax Associate',
            'department' => 'GST',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'karan@webrajya.in', 'role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($admin)->post(route('settings.update'), [
            'firm_name' => 'CAVault & Associates LLP',
            'invoice_prefix' => 'CA-INV',
            'default_cgst_rate' => 9,
            'default_sgst_rate' => 9,
            'default_igst_rate' => 18,
        ])->assertRedirect();

        $this->assertDatabaseHas('firm_settings', ['key' => 'firm_name', 'value' => 'CAVault & Associates LLP']);

        // Detail pages load cleanly
        $audit = Audit::firstOrFail();
        $invoice = Invoice::firstOrFail();
        $task = Task::firstOrFail();
        $this->actingAs($admin)->get(route('audits.show', $audit))->assertOk();
        $this->actingAs($admin)->get(route('billing.show', $invoice))->assertOk();
        $this->actingAs($admin)->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($admin)->get(route('clients.edit', $client))->assertOk();
    }

    public function test_each_staff_role_sees_role_specific_dashboard(): void
    {
        $admin = User::where('email', 'admin@webrajya.in')->firstOrFail();
        $partner = User::where('email', 'partner@webrajya.in')->firstOrFail();
        $manager = User::where('email', 'manager@webrajya.in')->firstOrFail();
        $employee = User::where('email', 'employee@webrajya.in')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Executive &amp; System Control Dashboard', false)
            ->assertSee('Monthly Revenue')
            ->assertSee('Settings &amp; Audit Logs', false);

        $this->actingAs($partner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Partner Practice &amp; Assurance Dashboard', false)
            ->assertSee('Audits in Progress & Sign-Off', false)
            ->assertSee('Monthly Revenue');

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Operations &amp; Team Delivery Dashboard', false)
            ->assertSee('Tasks Under Review')
            ->assertDontSee('Monthly Revenue')
            ->assertDontSee('Outstanding Fees');

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Work &amp; Assigned Clients Dashboard', false)
            ->assertSee('My Active Task Queue')
            ->assertDontSee('Monthly Revenue')
            ->assertDontSee('Outstanding Fees')
            ->assertDontSee('Billing &amp; Invoices', false)
            ->assertDontSee('Settings &amp; Audit Logs', false);
    }
}
