<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\EncounterService;
use App\Services\LocationService;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SatuSehatController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private MasterDataService $masterDataService,
        private LocationService $locationService,
        private EncounterService $encounterService,
    ) {}

    public function testSetup(): JsonResponse
    {
        return response()->json([
            'status'=> 'OK',
            'message'=> 'SatuSehat Integration API is running',
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
    // Stream 5 — Encounter

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik_pasien' => ['required','digits:16'],
            'nik_dokter' => ['required','digits:16'],
        ]);

        $result = $this->encounterService->createEncounter(
            $validated['nik_pasien'],
            $validated['nik_dokter'],
        );

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json($result, $statusCode);
    }

    public function updateEncounterInProgress(string $id): JsonResponse
    {
        $result = $this->encounterService->updateToInProgress($id);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }
}