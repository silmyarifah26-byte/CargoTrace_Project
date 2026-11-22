<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('exception_type', 40);
            $table->string('severity', 20)->default('LOW'); // LOW, MEDIUM, HIGH
            $table->string('status', 20)->default('OPEN'); // OPEN, INVESTIGATING, RESOLVED
            $table->text('notes')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'status']);
            $table->index('exception_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_exceptions');
    }
};