<?php

namespace App\Exceptions;

use App\Enums\ShipmentStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class InvalidStatusTransition extends RuntimeException
{
    public function __construct(
        public readonly ShipmentStatus $from,
        public readonly ShipmentStatus $to,
    ) {
        parent::__construct("A shipment cannot move from {$from->value} to {$to->value}.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'allowed' => array_map(fn (ShipmentStatus $s) => $s->value, $this->from->allowedTransitions()),
        ], 409);
    }
}
