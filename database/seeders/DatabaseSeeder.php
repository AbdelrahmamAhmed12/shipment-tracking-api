<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneRate;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data for local development only.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Operations Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Demo Client',
            'email' => 'client@example.com',
        ]);

        $zones = collect([
            ['name' => 'Riyadh', 'code' => 'RUH'],
            ['name' => 'Jeddah', 'code' => 'JED'],
            ['name' => 'Dammam', 'code' => 'DMM'],
        ])->map(fn (array $zone) => Zone::create($zone));

        foreach ($zones as $origin) {
            foreach ($zones as $destination) {
                $sameCity = $origin->is($destination);

                ZoneRate::create([
                    'origin_zone_id' => $origin->id,
                    'destination_zone_id' => $destination->id,
                    'base_weight_grams' => 5000,
                    'base_price' => $sameCity ? 1500 : 2500,
                    'extra_kg_price' => $sameCity ? 100 : 200,
                ]);
            }
        }
    }
}
