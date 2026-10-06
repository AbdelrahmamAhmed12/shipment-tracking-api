<?php

namespace Tests\Unit;

use App\Enums\ShipmentStatus;
use PHPUnit\Framework\TestCase;

class ShipmentStatusTest extends TestCase
{
    public function test_pending_shipment_can_be_picked_up_or_cancelled(): void
    {
        $this->assertTrue(ShipmentStatus::Pending->canTransitionTo(ShipmentStatus::PickedUp));
        $this->assertTrue(ShipmentStatus::Pending->canTransitionTo(ShipmentStatus::Cancelled));
    }

    public function test_pending_shipment_cannot_jump_to_delivered(): void
    {
        $this->assertFalse(ShipmentStatus::Pending->canTransitionTo(ShipmentStatus::Delivered));
    }

    public function test_failed_attempt_can_be_retried_or_returned(): void
    {
        $this->assertTrue(ShipmentStatus::FailedAttempt->canTransitionTo(ShipmentStatus::OutForDelivery));
        $this->assertTrue(ShipmentStatus::FailedAttempt->canTransitionTo(ShipmentStatus::Returned));
    }

    public function test_final_statuses_allow_no_further_change(): void
    {
        foreach ([ShipmentStatus::Delivered, ShipmentStatus::Returned, ShipmentStatus::Cancelled] as $status) {
            $this->assertTrue($status->isFinal());
            $this->assertSame([], $status->allowedTransitions());
        }
    }

    public function test_a_status_never_transitions_to_itself(): void
    {
        foreach (ShipmentStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status));
        }
    }
}
