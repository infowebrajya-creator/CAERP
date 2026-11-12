<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('key_hash', 64)->unique();
            $table->text('license_key');
            $table->string('licensed_to', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('machine_fingerprint', 255)->nullable()->index();
            $table->string('status', 50)->default('active')->index(); // active, expired, suspended, trial
            $table->string('edition', 100)->default('Lifetime Edition');
            $table->unsignedInteger('max_staff_users')->default(25);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('amc_expires_at')->nullable(); // AMC expiry (e.g. 1 year from activation)
            $table->timestamp('last_verified_at')->nullable();
            $table->text('offline_token')->nullable(); // Cryptographically signed hardware payload
            $table->json('meta_data')->nullable(); // Additional metadata (OS, activation history, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_licenses');
    }
};
