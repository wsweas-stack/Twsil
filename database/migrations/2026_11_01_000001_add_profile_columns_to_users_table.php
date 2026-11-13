<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Approval workflow for Driver/Employee public registrations.
            // Customers are activated immediately; Admin reviews the rest.
            $table->string('approval_status', 20)->default('approved')->index(); // pending|approved|rejected
            $table->string('phone', 32)->nullable();
            $table->string('national_id', 64)->nullable();      // required for Driver/Employee
            $table->string('city', 100)->nullable();
            $table->string('vehicle_type', 64)->nullable();     // Driver only
            $table->string('license_number', 64)->nullable();   // Driver only
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status', 'phone', 'national_id', 'city',
                'vehicle_type', 'license_number', 'rejection_reason', 'approved_at',
            ]);
        });
    }
};
