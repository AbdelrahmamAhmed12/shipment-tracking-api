<?php

namespace App\Services;

use App\Models\Shipment;

class TrackingNumberGenerator
{
    /** Letters and digits that are hard to confuse when read aloud. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        do {
            $number = 'ST'.$this->randomString(10);
        } while (Shipment::query()->where('tracking_number', $number)->exists());

        return $number;
    }

    private function randomString(int $length): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= self::ALPHABET[random_int(0, $max)];
        }

        return $result;
    }
}
