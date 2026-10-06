<?php

namespace App\Events;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;

class ShipmentStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ?ShipmentStatus $from,
        public readonly ShipmentStatus $to,
    ) {}
}
