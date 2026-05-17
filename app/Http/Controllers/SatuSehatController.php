<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class SatusehatController extends Controller
{
    public function testSetup(): JsonResponse
    {
        return response()->json([
            'status' => 'OK',
            'message' => 'SATUSEHAT Integration API is running',
            'environment' => config('app.env'),
        ], 200);
    }

    // Stream 2: Auth
    // Stream 3: IHS Number
    // Stream 4: Location
    // Stream 5: Encounter
}