<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    /**
     * Clients see their own shipments. Admins see all of them.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['sometimes', Rule::enum(ShipmentStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();

        $shipments = Shipment::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->with(['originZone', 'destinationZone'])
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15);

        return ShipmentResource::collection($shipments);
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $shipment = $this->shipments->create($request->user(), $request->validated());

        return ShipmentResource::make($shipment->load(['originZone', 'destinationZone', 'statusLogs']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Shipment $shipment): ShipmentResource
    {
        Gate::authorize('view', $shipment);

        return ShipmentResource::make($shipment->load(['originZone', 'destinationZone', 'statusLogs']));
    }
}
