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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_number', 20)->unique();
            $table->string('reference', 100)->nullable();
            $table->foreignId('origin_zone_id')->constrained('zones');
            $table->foreignId('destination_zone_id')->constrained('zones');
            $table->string('receiver_name', 150);
            $table->string('receiver_phone', 30);
            $table->string('receiver_address', 500);
            $table->unsignedInteger('weight_grams');
            $table->unsignedInteger('cod_amount')->default(0);
            $table->unsignedInteger('shipping_fee');
            $table->char('currency', 3);
            $table->string('status', 30);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // One client cannot reuse an order reference.
            $table->unique(['user_id', 'reference']);
            // Listing a client's shipments filtered by status.
            $table->index(['user_id', 'status']);
        });

        Schema::create('shipment_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_status_logs');
        Schema::dropIfExists('shipments');
    }
};
