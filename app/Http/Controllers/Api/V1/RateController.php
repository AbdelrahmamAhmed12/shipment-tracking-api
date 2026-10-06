<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRateRequest;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;

class RateController extends Controller
{
    public function quote(QuoteRateRequest $request, PricingService $pricing): JsonResponse
    {
        $fee = $pricing->quote(
            (int) $request->validated('origin_zone_id'),
            (int) $request->validated('destination_zone_id'),
            (int) $request->validated('weight_grams'),
        );

        return response()->json([
            'data' => [
                'shipping_fee' => $fee,
                'currency' => config('shipping.currency'),
            ],
        ]);
    }
}
