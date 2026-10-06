<?php

namespace App\Http\Resources;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Shipment */
class ShipmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'tracking_number' => $this->tracking_number,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'origin_zone' => $this->whenLoaded('originZone', fn () => [
                'id' => $this->originZone->id,
                'name' => $this->originZone->name,
            ]),
            'destination_zone' => $this->whenLoaded('destinationZone', fn () => [
                'id' => $this->destinationZone->id,
                'name' => $this->destinationZone->name,
            ]),
            'receiver' => [
                'name' => $this->receiver_name,
                'phone' => $this->receiver_phone,
                'address' => $this->receiver_address,
            ],
            'weight_grams' => $this->weight_grams,
            'cod_amount' => $this->cod_amount,
            'shipping_fee' => $this->shipping_fee,
            'currency' => $this->currency,
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'history' => StatusLogResource::collection($this->whenLoaded('statusLogs')),
        ];
    }
}
