<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebhookEndpointRequest;
use App\Http\Resources\WebhookEndpointResource;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class WebhookEndpointController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return WebhookEndpointResource::collection(
            $request->user()->webhookEndpoints()->latest('id')->get()
        );
    }

    /**
     * The signing secret is returned once, here, and never again.
     */
    public function store(StoreWebhookEndpointRequest $request): JsonResponse
    {
        $secret = Str::random(40);

        $endpoint = $request->user()->webhookEndpoints()->create([
            'url' => $request->validated('url'),
            'secret' => $secret,
            'is_active' => true,
        ]);

        return WebhookEndpointResource::make($endpoint)
            ->additional(['secret' => $secret])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, WebhookEndpoint $webhookEndpoint): Response
    {
        abort_unless($webhookEndpoint->user_id === $request->user()->id, 404);

        $webhookEndpoint->delete();

        return response()->noContent();
    }
}
