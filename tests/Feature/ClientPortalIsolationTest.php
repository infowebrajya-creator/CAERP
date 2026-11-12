<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientPortalIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_client_cannot_access_staff_routes_and_sees_own_portal(): void
    {
        $clientUser1 = User::where('email', 'client@apexprecision.in')->firstOrFail();

        $this->actingAs($clientUser1)
            ->get(route('dashboard'))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($clientUser1)->get(route('portal.dashboard'))->assertOk();
        $this->actingAs($clientUser1)->get(route('portal.profile'))->assertOk();
        $this->actingAs($clientUser1)->get(route('portal.documents'))->assertOk();
        $this->actingAs($clientUser1)->get(route('portal.filings'))->assertOk();
        $this->actingAs($clientUser1)->get(route('portal.invoices'))->assertOk();
        $this->actingAs($clientUser1)->get(route('portal.messages'))->assertOk();
    }

    public function test_client_data_isolation_prevents_accessing_other_client_documents_or_invoices(): void
    {
        $clientUser1 = User::where('email', 'client@apexprecision.in')->firstOrFail();
        $client2 = Client::where('email', 'ananya@kapoorholdings.in')->firstOrFail();

        $client2Doc = Document::create([
            'client_id' => $client2->id,
            'title' => 'Private Client 2 Tax Doc',
            'category' => 'pan',
            'status' => 'uploaded',
            'is_shared_with_client' => true,
            'current_version' => 1,
            'file_path' => 'documents/client_2/secret.txt',
            'original_filename' => 'secret.txt',
        ]);
        Storage::disk('local')->put('documents/client_2/secret.txt', 'confidential');

        $client2Invoice = Invoice::create([
            'invoice_number' => 'CA-INV-C2-001',
            'client_id' => $client2->id,
            'invoice_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'tax_mode' => 'exempt',
            'subtotal' => 5000,
            'taxable_amount' => 5000,
            'total_amount' => 5000,
            'balance_amount' => 5000,
            'status' => 'unpaid',
        ]);

        // Client 1 must get 403 when trying to download Client 2's document or invoice PDF
        $this->actingAs($clientUser1)
            ->get(route('portal.documents.download', $client2Doc))
            ->assertForbidden();

        $this->actingAs($clientUser1)
            ->get(route('portal.invoices.pdf', $client2Invoice))
            ->assertForbidden();

        // Client 1 can upload their own requested document and send a message
        $client1 = $clientUser1->clientProfile;
        $requestedDoc = $client1->documents()->where('status', 'requested')->firstOrFail();

        $this->actingAs($clientUser1)
            ->post(route('portal.documents.upload'), [
                'document_id' => $requestedDoc->id,
                'file' => UploadedFile::fake()->create('hdfc_q2.pdf', 80, 'application/pdf'),
            ])->assertRedirect();

        $this->assertEquals('uploaded', $requestedDoc->fresh()->status);

        $this->actingAs($clientUser1)
            ->post(route('portal.messages.send'), [
                'subject' => 'Uploaded Q2 statement',
                'message' => 'Please confirm receipt.',
            ])->assertRedirect();

        $this->assertDatabaseHas('communications', [
            'client_id' => $client1->id,
            'sender_type' => 'client',
            'subject' => 'Uploaded Q2 statement',
        ]);
    }

    public function test_client_sees_only_their_own_companies_and_documents(): void
    {
        $clientUser = User::where('email', 'client@apexprecision.in')->firstOrFail();
        $ownClient = $clientUser->clientProfile;
        $otherClient = Client::where('id', '!=', $ownClient->id)->firstOrFail();

        $secondCompany = Client::create([
            'client_code' => 'CA-CL-9902',
            'user_id' => $clientUser->id,
            'category' => 'business',
            'name' => 'Apex Holdings LLP',
            'email' => 'holdings@apexprecision.in',
            'status' => 'active',
        ]);

        $secondDoc = Document::create([
            'client_id' => $secondCompany->id,
            'title' => 'Apex Holdings Incorporation Deed',
            'category' => 'legal',
            'status' => 'requested',
            'is_shared_with_client' => true,
        ]);

        $otherDoc = Document::create([
            'client_id' => $otherClient->id,
            'title' => 'Confidential Other Client Doc',
            'category' => 'legal',
            'status' => 'approved',
            'is_shared_with_client' => true,
        ]);

        $this->actingAs($clientUser)
            ->get(route('portal.profile'))
            ->assertOk()
            ->assertSee($ownClient->name)
            ->assertSee($secondCompany->name)
            ->assertDontSee($otherClient->name);

        $this->actingAs($clientUser)
            ->get(route('portal.documents'))
            ->assertOk()
            ->assertSee($secondDoc->title)
            ->assertDontSee($otherDoc->title)
            ->assertDontSee($otherClient->name);

        $this->actingAs($clientUser)
            ->get(route('portal.documents.download', $otherDoc))
            ->assertForbidden();
    }

    public function test_employee_sees_only_assigned_projects_and_clients(): void
    {
        $employee = User::where('email', 'employee@webrajya.in')->firstOrFail();
        $partner = User::where('email', 'partner@webrajya.in')->firstOrFail();

        $assignedClient = Client::where('assigned_ca_id', $employee->id)->firstOrFail();
        $unassignedClient = Client::where('assigned_ca_id', '!=', $employee->id)
            ->whereDoesntHave('staff', fn ($q) => $q->where('users.id', $employee->id))
            ->firstOrFail();

        $assignedTask = \App\Models\Task::create([
            'title' => 'Assigned Employee Task',
            'client_id' => $assignedClient->id,
            'assigned_to' => $employee->id,
            'created_by' => $partner->id,
            'category' => 'gst',
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        $unassignedTask = \App\Models\Task::create([
            'title' => 'Other Staff Task On Assigned Client',
            'client_id' => $assignedClient->id,
            'assigned_to' => $partner->id,
            'created_by' => $partner->id,
            'category' => 'audit',
            'priority' => 'medium',
            'status' => 'todo',
        ]);

        $assignedAudit = \App\Models\Audit::create([
            'client_id' => $assignedClient->id,
            'title' => 'Employee Lead Audit',
            'audit_type' => 'internal_audit',
            'financial_year' => '2025-26',
            'lead_auditor_id' => $employee->id,
            'status' => 'in_progress',
        ]);

        $unassignedAudit = \App\Models\Audit::create([
            'client_id' => $unassignedClient->id,
            'title' => 'Partner Only Statutory Audit',
            'audit_type' => 'statutory',
            'financial_year' => '2025-26',
            'lead_auditor_id' => $partner->id,
            'status' => 'in_progress',
        ]);

        // Employee sees assigned client, not unassigned client
        $this->actingAs($employee)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertSee($assignedClient->name)
            ->assertDontSee($unassignedClient->name);

        $this->actingAs($employee)
            ->get(route('clients.show', $unassignedClient))
            ->assertForbidden();

        // Employee sees only assigned tasks/projects
        $this->actingAs($employee)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee($assignedTask->title)
            ->assertDontSee($unassignedTask->title);

        $this->actingAs($employee)
            ->get(route('tasks.show', $assignedTask))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route('tasks.show', $unassignedTask))
            ->assertForbidden();

        // Employee sees only assigned audits
        $this->actingAs($employee)
            ->get(route('audits.index'))
            ->assertOk()
            ->assertSee($assignedAudit->title)
            ->assertDontSee($unassignedAudit->title);

        $this->actingAs($employee)
            ->get(route('audits.show', $unassignedAudit))
            ->assertForbidden();
    }
}

