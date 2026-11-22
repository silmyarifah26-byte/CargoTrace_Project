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
            $table->string('shipment_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers')->restrictOnDelete();
            $table->foreignId('origin_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->restrictOnDelete();

            $table->string('group_code', 10)->default('A'); // A (12h), B (18h), C (24h)
            $table->unsignedInteger('delay_threshold_hours')->default(12);
            $table->string('cargo_type', 100);
            $table->decimal('weight_kg', 12, 2);
            $table->decimal('volume_m3', 12, 3)->nullable();

            $table->timestamp('expected_departure_at')->nullable();
            $table->timestamp('expected_arrival_at')->nullable();
            $table->timestamp('estimated_arrival_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->string('current_status', 30)->default('CREATED');
            $table->boolean('is_delayed')->default(false);
            $table->unsignedInteger('delay_hours')->default(0);
            $table->string('delay_severity', 20)->nullable(); // LOW, MEDIUM, HIGH
            $table->text('delay_reason')->nullable();

            $table->timestamps();

            $table->index(['current_status', 'is_delayed']);
            $table->index(['group_code', 'carrier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};