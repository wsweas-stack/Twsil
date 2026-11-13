<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // who paid
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->index(); // PaymentStatus: pending|succeeded|failed|cancelled
            $table->string('stripe_session_id', 255)->unique()->nullable();
            $table->string('stripe_payment_intent_id', 255)->unique()->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->unique()->constrained()->cascadeOnDelete(); // one per shipment
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('delivery_fee', 10, 2);
            $table->decimal('driver_commission', 10, 2);
            $table->decimal('company_share', 10, 2);
            $table->decimal('driver_commission_rate', 5, 2); // snapshot of rate used
            $table->string('status', 20)->default('unpaid')->index(); // unpaid|paid
            $table->timestamp('paid_at')->nullable();
            $table->text('payment_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('commission_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // commission|payout
            $table->decimal('amount', 10, 2); // + commission, - payout
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('payments');
    }
};
