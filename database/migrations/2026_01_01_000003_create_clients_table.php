<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_code', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_ca_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 20)->default('business')->index(); // individual, business
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('mobile', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 15)->nullable();
            $table->string('pan', 20)->nullable()->index();
            $table->string('gstin', 20)->nullable()->index();
            $table->string('tan', 20)->nullable();
            $table->string('cin', 30)->nullable();
            $table->string('business_type', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->date('registration_date')->nullable();
            $table->string('status', 20)->default('active')->index(); // active, inactive, onboarding
            $table->boolean('is_gst_registered')->default(false)->index();
            $table->boolean('is_itr_applicable')->default(true)->index();
            $table->boolean('is_tds_applicable')->default(false)->index();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('client_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_label', 60)->default('Assigned Staff');
            $table->timestamps();
            $table->unique(['client_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_staff');
        Schema::dropIfExists('clients');
    }
};

