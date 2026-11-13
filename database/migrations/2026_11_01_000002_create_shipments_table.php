<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tracking_number', 32)->unique();
            $table->string('status', 30)->index(); // ShipmentStatus enum value
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Sender
            $table->string('sender_name', 120);
            $table->string('sender_phone', 32);
            $table->string('sender_city', 100);
            $table->text('sender_address');
            $table->decimal('sender_latitude', 10, 7)->nullable();
            $table->decimal('sender_longitude', 10, 7)->nullable();

            // Receiver
            $table->string('receiver_name', 120);
            $table->string('receiver_phone', 32);
            $table->string('receiver_city', 100);
            $table->text('receiver_address');
            $table->decimal('receiver_latitude', 10, 7)->nullable();
            $table->decimal('receiver_longitude', 10, 7)->nullable();

            // Parcel details
            $table->string('description');
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->string('invoice_path')->nullable(); // uploaded PDF (protected disk)

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['driver_id', 'status']);
        });

        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30);          // status after the event
            $table->string('type', 40)->nullable(); // e.g. pickup_scan, delivery_otp, status_change
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['shipment_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipments');
    }
};
