<?php

namespace Database\Factories;

use App\Models\Zone;
use App\Models\ZoneRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZoneRate>
 */
class ZoneRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'origin_zone_id' => Zone::factory(),
            'destination_zone_id' => Zone::factory(),
            'base_weight_grams' => 5000,
            'base_price' => 2500,
            'extra_kg_price' => 200,
        ];
    }
}
