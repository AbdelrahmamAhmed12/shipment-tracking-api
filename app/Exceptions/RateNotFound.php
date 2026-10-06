<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class RateNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No shipping rate is configured between these zones.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
