<?php

namespace Tests\Feature;

use App\Exceptions\RateNotFound;
use App\Models\Zone;
use App\Models\ZoneRate;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rate under test: 25.00 covers the first 5 kg, then 2.00 per started kg.
     *
     * @return array<string, array{int, int}>
     */
    public static function weights(): array
    {
        return [
            'light parcel pays the base price' => [1000, 2500],
            'exactly the base weight pays the base price' => [5000, 2500],
            'one gram over is charged a full extra kilogram' => [5001, 2700],
            'two extra kilograms' => [7000, 2900],
            'part of a third extra kilogram is rounded up' => [7200, 3100],
        ];
    }

    #[DataProvider('weights')]
    public function test_it_prices_by_zone_and_weight(int $weightGrams, int $expectedFee): void
    {
        $rate = ZoneRate::factory()->create([
            'base_weight_grams' => 5000,
            'base_price' => 2500,
            'extra_kg_price' => 200,
        ]);

        $fee = app(PricingService::class)->quote($rate->origin_zone_id, $rate->destination_zone_id, $weightGrams);

        $this->assertSame($expectedFee, $fee);
    }

    public function test_it_fails_when_no_rate_exists_between_the_zones(): void
    {
        $origin = Zone::factory()->create();
        $destination = Zone::factory()->create();

        $this->expectException(RateNotFound::class);

        app(PricingService::class)->quote($origin->id, $destination->id, 1000);
    }

    public function test_a_rate_only_applies_in_its_own_direction(): void
    {
        $rate = ZoneRate::factory()->create();

        $this->expectException(RateNotFound::class);

        app(PricingService::class)->quote($rate->destination_zone_id, $rate->origin_zone_id, 1000);
    }
}
