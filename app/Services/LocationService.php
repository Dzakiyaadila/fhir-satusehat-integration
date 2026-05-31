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

    public function getOrCreateLocation(): array
    {
        // Cek database lokal dulu
        $existingLocation = Location::first();

        if ($existingLocation) {
            return [
                'success'    => true,
                'message'    => 'Location sudah ada di database',
                'location_id' => $existingLocation->location_id_satusehat,
                'data'       => $existingLocation,
            ];
        }

        // Belum ada — POST ke SATUSEHAT
        $token   = $this->authService->getAccessToken();
        $orgId   = env('SATUSEHAT_ORG_ID');
        $baseUrl = env('SATUSEHAT_BASE_URL');

        // Payload lengkap sesuai Postman publik SATUSEHAT
        $payload = [
            'resourceType' => 'Location',
            'identifier'   => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/location/' . $orgId,
                    'value'  => 'SS-UKP-POLI-ROOM',
                ],
            ],
            'status'      => 'active',
            'name'        => 'Ruang Poli Umum',
            'description' => 'Ruang Poli Umum',
            'mode'        => 'instance',
            'telecom'     => [
                ['system' => 'phone', 'value' => '+621500567', 'use' => 'work'],
                ['system' => 'email', 'value' => 'admin@klinik.com', 'use' => 'work'],
                ['system' => 'url',   'value' => 'klinik.com', 'use' => 'work'],
            ],
            'physicalType' => [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/location-physical-type',
                        'code'    => 'ro',
                        'display' => 'Room',
                    ],
                ],
            ],
            'position' => [
                'longitude' => 110.3695,
                'latitude'  => -7.7956,
                'altitude'  => 0,
            ],
            'managingOrganization' => [
                'reference' => 'Organization/' . $orgId,
            ],
        ];

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($baseUrl . '/Location', $payload);

        // Auto-refresh token jika 401
        if ($response->status() === 401) {
            $token    = $this->authService->forceNewToken();
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/Location', $payload);
        }

        if ($response->status() === 201) {
            $responseData = $response->json();

            $location = Location::create([
                'nama_ruangan'          => 'Ruang Poli Umum',
                'location_id_satusehat' => $responseData['id'],
                'org_id'                => $orgId,
            ]);

            return [
                'success'             => true,
                'message'             => 'Location berhasil dibuat',
                'location_id'         => $responseData['id'],
                'data'                => $location,
                'satusehat_response'  => $responseData,
            ];
        }

        return [
            'success'  => false,
            'message'  => 'Gagal membuat location di SATUSEHAT. HTTP ' . $response->status(),
            'response' => $response->json(),
        ];
    }
}