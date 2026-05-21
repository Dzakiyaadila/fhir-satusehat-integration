<?php

namespace App\Services; // Perhatikan huruf S besar

use Illuminate\Support\Facades\Http;
use App\Models\IhsLookup; // Tambahan untuk Stream 3 (Cache DB)

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
        // Cek DB Lokal Dulu (Tambahan Stream 3)
        $cached = IhsLookup::where('nik', $nik)->where('tipe', 'pasien')->first();
        if ($cached) {
            if (!$cached->ditemukan) {
                return [
                    'success' => false,
                    'message' => 'NIK Pasien tidak terdaftar di SATUSEHAT. (Dari Cache)',
                    'fallback_id' => '100000000001'
                ];
            }
            return [
                'success' => true,
                'ihs_number' => $cached->ihs_number
            ];
        }

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
            // Simpan hasil "tidak ditemukan" ke DB (Tambahan Stream 3)
            IhsLookup::create(['nik' => $nik, 'tipe' => 'pasien', 'ditemukan' => false]);

            return [
                'success' => false,
                'message' => 'NIK Pasien tidak terdaftar di SATUSEHAT.',
                'fallback_id' => '100000000001'
            ];
        }

        $ihsNumber = $response->json()['entry'][0]['resource']['id'];
        
        // Simpan hasil "ditemukan" ke DB (Tambahan Stream 3)
        IhsLookup::create(['nik' => $nik, 'tipe' => 'pasien', 'ihs_number' => $ihsNumber, 'ditemukan' => true]);

        return [
            'success' => true,
            'ihs_number' => $ihsNumber
        ];
    }

    /**
     * Kriteria: Mencari IHS Number Dokter (Practitioner) berdasarkan NIK
     */
    public function getPractitionerIhs(string $nik): array
    {
        // Cek DB Lokal Dulu (Tambahan Stream 3)
        $cached = IhsLookup::where('nik', $nik)->where('tipe', 'dokter')->first();
        if ($cached) {
            if (!$cached->ditemukan) {
                return [
                    'success' => false,
                    'message' => 'NIK Dokter tidak terdaftar di SATUSEHAT. (Dari Cache)',
                    'fallback_id' => 'N10000001'
                ];
            }
            return [
                'success' => true,
                'ihs_number' => $cached->ihs_number
            ];
        }

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
            // Simpan hasil "tidak ditemukan" ke DB (Tambahan Stream 3)
            IhsLookup::create(['nik' => $nik, 'tipe' => 'dokter', 'ditemukan' => false]);

            return [
                'success' => false,
                'message' => 'NIK Dokter tidak terdaftar di SATUSEHAT.',
                'fallback_id' => 'N10000001'
            ];
        }

        $ihsNumber = $response->json()['entry'][0]['resource']['id'];

        // Simpan hasil "ditemukan" ke DB (Tambahan Stream 3)
        IhsLookup::create(['nik' => $nik, 'tipe' => 'dokter', 'ihs_number' => $ihsNumber, 'ditemukan' => true]);

        return [
            'success' => true,
            'ihs_number' => $ihsNumber
        ];
    }
}