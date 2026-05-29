<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Http;

class LocationService
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getOrCreateLocation()
    {
        // cek database lokal dulu
        $existingLocation = Location::first();

        if ($existingLocation) {
            return [
                'success' => true,
                'message' => 'Location sudah ada di database',
                'data' => $existingLocation
            ];
        }

        $token = $this->authService->getAccessToken();

        $payload = [
            "resourceType" => "Location",
            "status" => "active",
            "name" => "Ruang Poli Umum",
            "description" => "Ruang Poli Umum",
            "mode" => "instance",
            "managingOrganization" => [
                "reference" => "Organization/" . env('SATUSEHAT_ORG_ID')
            ]
        ];

        $response = Http::withToken($token)
            ->post(
                env('SATUSEHAT_BASE_URL') . '/Location',
                $payload
            );

        if ($response->status() == 201) {

            $responseData = $response->json();

            $location = Location::create([
                'nama_ruangan' => 'Ruang Poli Umum',
                'location_id_satusehat' => $responseData['id'],
                'org_id' => env('SATUSEHAT_ORG_ID')
            ]);

            return [
                'success' => true,
                'message' => 'Location berhasil dibuat',
                'data' => $location,
                'satusehat_response' => $responseData
            ];
        }

        return [
            'success' => false,
            'message' => 'Gagal membuat location',
            'response' => $response->json()
        ];
    }
}