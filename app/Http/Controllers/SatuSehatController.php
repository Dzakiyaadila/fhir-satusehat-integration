<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class SatuSehatController extends Controller
{
    protected $authService;
    protected $masterService;

    public function __construct(AuthService $authService, MasterDataService $masterService)
    {
        $this->authService = $authService;
        $this->masterService = $masterService;
    }

    public function daftarkanPasien(): JsonResponse
    {
        try {
            // -----------------------------------------------------------------
            // STREAM 2: AUTHENTICATION (Otentikasi & Cache via Service)
            // -----------------------------------------------------------------
            $token = $this->authService->getAccessToken();

            
            // =================================================================
            // SILAKAN TEMAN YANG MENGERJAKAN STREAM 3 LANJUT DI BAWAH SINI
            // (Tinggal ambil variabel $ihsPasien, $ihsDokter, dan $locationId untuk disave ke DB & Encounter)
            // =================================================================

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}