<?php

namespace App\Services;

use App\Exceptions\RateNotFound;
use App\Models\ZoneRate;

class PricingService
{
    /**
     * Shipping fee in the smallest currency unit.
     *
     * The base price covers the base weight. Every started kilogram above it
     * is charged at the extra-kilogram price.
     *
     * @throws RateNotFound
     */
    public function quote(int $originZoneId, int $destinationZoneId, int $weightGrams): int
    {
        $rate = ZoneRate::query()
            ->where('origin_zone_id', $originZoneId)
            ->where('destination_zone_id', $destinationZoneId)
            ->first();

        if ($rate === null) {
            throw new RateNotFound;
        }

        $extraGrams = max(0, $weightGrams - $rate->base_weight_grams);
        $extraKilograms = (int) ceil($extraGrams / 1000);

        return $rate->base_price + ($extraKilograms * $rate->extra_kg_price);
    }
}
