<?php

namespace App\Services; // Perhatikan huruf S besar

use Illuminate\Support\Facades\Http;

class MasterDataService
{
    protected $baseUrl;
    protected $authService;

    // Inject AuthService ke dalam MasterDataService
    public function __construct(AuthService $authService)
    {
        $this->baseUrl = env('SATUSEHAT_BASE_URL');
        $this->authService = $authService;
    }

    /**
     * Kriteria: Mencari IHS Number Pasien berdasarkan NIK
     */
    public function getPatientIhs(string $nik): array
    {
        $token = $this->authService->getAccessToken();
        
        $response = Http::withToken($token)->get($this->baseUrl . "/Patient", [
            'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik
        ]);

        if ($response->status() === 401) {
            $newToken = $this->authService->forceNewToken();
            $response = Http::withToken($newToken)->get($this->baseUrl . "/Patient", [
                'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik
            ]);
        }

        if ($response->status() === 404 || empty($response->json()['entry'])) {
            return [
                'success' => false,
                'message' => 'NIK Pasien tidak terdaftar di SATUSEHAT.',
                'fallback_id' => '100000000001'
            ];
        }

        return [
            'success' => true,
            'ihs_number' => $response->json()['entry'][0]['resource']['id']
        ];
    }

    /**
     * Kriteria: Mencari IHS Number Dokter (Practitioner) berdasarkan NIK
     */
    public function getPractitionerIhs(string $nik): array
    {
        $token = $this->authService->getAccessToken();

        $response = Http::withToken($token)->get($this->baseUrl . "/Practitioner", [
            'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik
        ]);

        if ($response->status() === 401) {
            $newToken = $this->authService->forceNewToken();
            $response = Http::withToken($newToken)->get($this->baseUrl . "/Practitioner", [
                'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik
            ]);
        }

        if ($response->status() === 404 || empty($response->json()['entry'])) {
            return [
                'success' => false,
                'message' => 'NIK Dokter tidak terdaftar di SATUSEHAT.',
                'fallback_id' => 'N10000001'
            ];
        }

        return [
            'success' => true,
            'ihs_number' => $response->json()['entry'][0]['resource']['id']
        ];
    }
}