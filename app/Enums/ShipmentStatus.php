<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case FailedAttempt = 'failed_attempt';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    /**
     * The statuses a shipment may move to from this one.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::PickedUp, self::Cancelled],
            self::PickedUp => [self::InTransit],
            self::InTransit => [self::OutForDelivery],
            self::OutForDelivery => [self::Delivered, self::FailedAttempt],
            self::FailedAttempt => [self::OutForDelivery, self::Returned],
            self::Delivered, self::Returned, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
