<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        Schema::create('zone_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('destination_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->unsignedInteger('base_weight_grams');
            // Money is stored as integers in the smallest currency unit.
            $table->unsignedInteger('base_price');
            $table->unsignedInteger('extra_kg_price');
            $table->timestamps();

            $table->unique(['origin_zone_id', 'destination_zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_rates');
        Schema::dropIfExists('zones');
    }
};
