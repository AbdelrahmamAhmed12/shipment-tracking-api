<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function view(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $shipment->user_id === $user->id;
    }

    public function updateStatus(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin();
    }
}
