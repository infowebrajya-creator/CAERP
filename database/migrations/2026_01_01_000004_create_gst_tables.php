<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gst_return_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('frequency', 20)->default('monthly'); // monthly, quarterly, annual
            $table->unsignedTinyInteger('default_due_day')->default(20);
            $table->unsignedTinyInteger('default_due_month_offset')->default(1);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gst_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('return_type_id')->nullable()->constrained('gst_return_types')->nullOnDelete();
            $table->string('return_type_code', 30)->index();
            $table->string('financial_year', 20)->index();
            $table->string('tax_period', 30);
            $table->unsignedTinyInteger('period_month')->nullable()->index();
            $table->unsignedSmallInteger('period_year')->nullable()->index();
            $table->date('due_date')->index();
            $table->string('status', 30)->default('not_started')->index();
            $table->string('arn', 60)->nullable();
            $table->date('filing_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->json('document_checklist')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gst_returns');
        Schema::dropIfExists('gst_return_types');
    }
};

