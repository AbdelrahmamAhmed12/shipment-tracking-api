<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tracking_number' => 'ST'.strtoupper(Str::random(10)),
            'reference' => null,
            'origin_zone_id' => Zone::factory(),
            'destination_zone_id' => Zone::factory(),
            'receiver_name' => fake()->name(),
            'receiver_phone' => fake()->e164PhoneNumber(),
            'receiver_address' => fake()->address(),
            'weight_grams' => 1000,
            'cod_amount' => 0,
            'shipping_fee' => 2500,
            'currency' => 'SAR',
            'status' => ShipmentStatus::Pending,
        ];
    }

    public function status(ShipmentStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
