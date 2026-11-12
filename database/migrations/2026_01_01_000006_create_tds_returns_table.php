<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tds_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('financial_year', 20)->index();
            $table->string('quarter', 10)->index(); // Q1, Q2, Q3, Q4
            $table->string('form_type', 20)->index(); // 24Q, 26Q, 27Q, 27EQ
            $table->decimal('deduction_amount', 14, 2)->default(0);
            $table->decimal('tax_deposited', 14, 2)->default(0);
            $table->string('challan_number', 50)->nullable();
            $table->string('bsr_code', 20)->nullable();
            $table->date('challan_date')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->date('filing_date')->nullable();
            $table->string('acknowledgement_number', 60)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tds_returns');
    }
};

