<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'origin_zone_id' => ['required', 'integer', Rule::exists('zones', 'id')],
            'destination_zone_id' => ['required', 'integer', Rule::exists('zones', 'id')],
            'weight_grams' => ['required', 'integer', 'min:1', 'max:'.config('shipping.max_weight_grams')],
        ];
    }
}
