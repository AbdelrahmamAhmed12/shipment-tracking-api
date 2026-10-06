<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusChanged;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly TrackingNumberGenerator $trackingNumbers,
    ) {}

    /**
     * Create a shipment with its price and first status log in one transaction.
     *
     * @param  array<string, mixed>  $data  Validated request data.
     */
    public function create(User $owner, array $data): Shipment
    {
        $fee = $this->pricing->quote(
            (int) $data['origin_zone_id'],
            (int) $data['destination_zone_id'],
            (int) $data['weight_grams'],
        );

        $shipment = DB::transaction(function () use ($owner, $data, $fee) {
            $shipment = $owner->shipments()->create([
                ...$data,
                'cod_amount' => $data['cod_amount'] ?? 0,
                'tracking_number' => $this->trackingNumbers->generate(),
                'shipping_fee' => $fee,
                'currency' => config('shipping.currency'),
                'status' => ShipmentStatus::Pending,
            ]);

            $shipment->statusLogs()->create([
                'from_status' => null,
                'to_status' => ShipmentStatus::Pending,
                'changed_by' => $owner->id,
            ]);

            return $shipment;
        });

        // Dispatched after commit, so listeners never see uncommitted data.
        ShipmentStatusChanged::dispatch($shipment, null, ShipmentStatus::Pending);

        return $shipment;
    }

    /**
     * Move a shipment to a new status and record who did it.
     *
     * The row is locked so two concurrent updates cannot both pass the
     * transition check against the same old status.
     *
     * @throws InvalidStatusTransition
     */
    public function changeStatus(Shipment $shipment, ShipmentStatus $to, ?User $actor = null, ?string $note = null): Shipment
    {
        [$shipment, $from] = DB::transaction(function () use ($shipment, $to, $actor, $note) {
            $locked = Shipment::query()->whereKey($shipment->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw new InvalidStatusTransition($from, $to);
            }

            $locked->update([
                'status' => $to,
                'delivered_at' => $to === ShipmentStatus::Delivered ? now() : $locked->delivered_at,
            ]);

            $locked->statusLogs()->create([
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor?->id,
                'note' => $note,
            ]);

            return [$locked, $from];
        });

        ShipmentStatusChanged::dispatch($shipment, $from, $to);

        return $shipment;
    }
}
