<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itr_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('assessment_year', 20)->index();
            $table->string('financial_year', 20)->index();
            $table->string('itr_category', 50)->default('Individual');
            $table->string('return_type', 30)->default('ITR-1')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_status', 30)->default('pending'); // pending, partial, complete
            $table->string('filing_status', 30)->default('not_started')->index();
            $table->date('due_date')->nullable()->index();
            $table->date('filing_date')->nullable();
            $table->string('acknowledgement_number', 60)->nullable();
            $table->string('refund_status', 30)->default('not_applicable');
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->json('pending_documents_checklist')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itr_filings');
    }
};

