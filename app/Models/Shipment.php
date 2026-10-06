<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tracking_number',
        'reference',
        'origin_zone_id',
        'destination_zone_id',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'weight_grams',
        'cod_amount',
        'shipping_fee',
        'currency',
        'status',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'weight_grams' => 'integer',
            'cod_amount' => 'integer',
            'shipping_fee' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Shipments are addressed by tracking number in URLs, never by ID.
     */
    public function getRouteKeyName(): string
    {
        return 'tracking_number';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** @return HasMany<ShipmentStatusLog, $this> */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ShipmentStatusLog::class)->orderBy('id');
    }
}
