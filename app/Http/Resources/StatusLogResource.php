<?php

namespace App\Http\Resources;

use App\Models\ShipmentStatusLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShipmentStatusLog */
class StatusLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'from' => $this->from_status?->value,
            'to' => $this->to_status->value,
            'note' => $this->note,
            'at' => $this->created_at?->toIso8601String(),
        ];
    }
}
