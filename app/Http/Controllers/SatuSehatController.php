<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;
use App\Models\Location;
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

    // =========================
    // STREAM 2: AUTH
    // =========================
    private function getAccessToken()
    {
        $response = Http::asForm()->post(
            env('SATUSEHAT_AUTH_URL') . '/accesstoken?grant_type=client_credentials',
            [
                'client_id' => env('SATUSEHAT_CLIENT_ID'),
                'client_secret' => env('SATUSEHAT_CLIENT_SECRET'),
            ]
        );

        if ($response->failed()) {
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }

    // =========================
    // STREAM 4: LOCATION
    // =========================
    public function postLocation(): JsonResponse
    {
        $existingLocation = Location::first();

        if ($existingLocation) {
            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Location already registered',
                'data' => $existingLocation
            ], 200);
        }

        $token = $this->getAccessToken();

        if (!$token) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed to get access token'
            ], 500);
        }

        $payload = [
            "resourceType" => "Location",
            "status" => "active",
            "name" => "Ruang Poli Umum",
            "managingOrganization" => [
                "reference" => "Organization/" . env('SATUSEHAT_ORG_ID')
            ]
        ];

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json'
            ])
            ->post(
                env('SATUSEHAT_BASE_URL') . '/Location',
                $payload
            );

        if ($response->failed()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed create location',
                'response' => $response->json()
            ], $response->status());
        }

        $locationId = $response->json()['id'] ?? null;

        $location = Location::create([
            'nama_ruangan' => 'Ruang Poli Umum',
            'location_id_satusehat' => $locationId,
            'org_id' => env('SATUSEHAT_ORG_ID')
        ]);

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Location created successfully',
            'data' => $location
        ], 201);
    }

    // =========================
    // STREAM 5: ENCOUNTER
    // =========================
    public function daftarkanPasien(): JsonResponse
    {
        try {

            $token = $this->authService->getAccessToken();

            return response()->json([
                'status' => 'success',
                'message' => 'Token berhasil diambil',
                'token' => $token
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}