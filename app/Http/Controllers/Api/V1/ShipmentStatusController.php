<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateShipmentStatusRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ShipmentService;

class ShipmentStatusController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    public function update(UpdateShipmentStatusRequest $request, Shipment $shipment): ShipmentResource
    {
        $shipment = $this->shipments->changeStatus(
            $shipment,
            ShipmentStatus::from($request->validated('status')),
            $request->user(),
            $request->validated('note'),
        );

        return ShipmentResource::make($shipment->load(['originZone', 'destinationZone', 'statusLogs']));
    }
}
