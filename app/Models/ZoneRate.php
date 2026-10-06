<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Price of sending a parcel from one zone to another.
 * All money values are stored as integers in the smallest currency unit.
 */
class ZoneRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_zone_id',
        'destination_zone_id',
        'base_weight_grams',
        'base_price',
        'extra_kg_price',
    ];

    protected function casts(): array
    {
        return [
            'base_weight_grams' => 'integer',
            'base_price' => 'integer',
            'extra_kg_price' => 'integer',
        ];
    }

    /** @return BelongsTo<Zone, $this> */
    public function originZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'origin_zone_id');
    }

    /** @return BelongsTo<Zone, $this> */
    public function destinationZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'destination_zone_id');
    }
}
