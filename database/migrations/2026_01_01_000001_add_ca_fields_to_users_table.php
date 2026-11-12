<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('employee')->after('email')->index();
            $table->string('employee_code', 30)->nullable()->unique()->after('role');
            $table->string('phone', 20)->nullable()->after('employee_code');
            $table->string('designation', 100)->nullable()->after('phone');
            $table->string('department', 100)->nullable()->after('designation');
            $table->boolean('is_active')->default(true)->after('department')->index();
            $table->string('avatar_color', 20)->default('#1e3a5f')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'employee_code',
                'phone',
                'designation',
                'department',
                'is_active',
                'avatar_color',
            ]);
        });
    }
};

