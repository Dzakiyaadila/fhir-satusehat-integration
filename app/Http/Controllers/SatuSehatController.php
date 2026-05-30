<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\LocationService;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;

class SatuSehatController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private MasterDataService $masterDataService,
        private LocationService $locationService,
    ) {}

    public function testSetup(): JsonResponse
    {
        return response()->json([
            'status'      => 'OK',
            'message'     => 'SatuSehat Integration API is running',
            'environment' => config('app.env'),
        ], 200);
    }

    /**
     * Setup lokasi klinik — POST sekali saat setup awal.
     * POST /api/location/setup
     */
    public function setupLocation(): JsonResponse
    {
        $result = $this->locationService->getOrCreateLocation();

        $statusCode = $result['success'] ? 200 : 500;

        return response()->json($result, $statusCode);
    }

    // Stream 5: Encounter
}