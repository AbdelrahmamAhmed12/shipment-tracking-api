<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentStatusLog;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    /**
     * Public tracking page data. It shows the journey only, never the
     * receiver's personal details or the price.
     */
    public function show(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::query()
            ->where('tracking_number', $trackingNumber)
            ->with(['originZone', 'destinationZone', 'statusLogs'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status->value,
                'status_label' => $shipment->status->label(),
                'origin' => $shipment->originZone->name,
                'destination' => $shipment->destinationZone->name,
                'delivered_at' => $shipment->delivered_at?->toIso8601String(),
                'history' => $shipment->statusLogs->map(fn (ShipmentStatusLog $log) => [
                    'status' => $log->to_status->value,
                    'at' => $log->created_at->toIso8601String(),
                ])->all(),
            ],
        ]);
    }
}
