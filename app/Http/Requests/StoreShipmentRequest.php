<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            // The client's own order number. Unique per client, so sending the
            // same order twice is rejected instead of creating two shipments.
            'reference' => [
                'nullable', 'string', 'max:100',
                Rule::unique('shipments', 'reference')->where('user_id', $this->user()->id),
            ],
            'origin_zone_id' => ['required', 'integer', Rule::exists('zones', 'id')],
            'destination_zone_id' => ['required', 'integer', Rule::exists('zones', 'id')],
            'receiver_name' => ['required', 'string', 'max:150'],
            'receiver_phone' => ['required', 'string', 'max:30'],
            'receiver_address' => ['required', 'string', 'max:500'],
            'weight_grams' => ['required', 'integer', 'min:1', 'max:'.config('shipping.max_weight_grams')],
            'cod_amount' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
