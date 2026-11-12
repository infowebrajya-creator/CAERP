<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('compliance_type', 30)->index(); // gst, itr, tds, advance_tax, roc, audit, custom
            $table->string('frequency', 20)->default('monthly'); // monthly, quarterly, annual, one_time
            $table->unsignedTinyInteger('due_day');
            $table->unsignedTinyInteger('due_month')->nullable();
            $table->unsignedTinyInteger('month_offset')->default(0);
            $table->unsignedSmallInteger('reminder_days_before')->default(5);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('compliance_deadlines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('compliance_type', 30)->index();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('compliance_rule_id')->nullable()->constrained('compliance_rules')->nullOnDelete();
            $table->date('due_date')->index();
            $table->date('reminder_date')->nullable()->index();
            $table->string('status', 30)->default('upcoming')->index(); // upcoming, in_progress, completed, overdue
            $table->string('priority', 20)->default('medium');
            $table->text('description')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_deadlines');
        Schema::dropIfExists('compliance_rules');
    }
};

