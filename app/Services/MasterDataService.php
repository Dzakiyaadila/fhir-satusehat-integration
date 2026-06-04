<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\IhsLookup;

class MasterDataService
{
    protected $baseUrl;
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->baseUrl = env('SATUSEHAT_BASE_URL');
        $this->authService = $authService;
    }

    public function getPatientIhs(string $nik): array
    {
        $cached = IhsLookup::where('nik', $nik)->where('tipe', 'pasien')->first();
        if ($cached) {
            if (!$cached->ditemukan) {
                throw new \Exception('NIK Pasien tidak terdaftar di SATUSEHAT.'); // FIX 1
            }
            return [
                'success' => true,
                'ihs_number' => $cached->ihs_number,
                'nama' => $cached->nama, // FIX 3
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
            IhsLookup::create(['nik' => $nik, 'tipe' => 'pasien', 'ditemukan' => false]);
            throw new \Exception('NIK Pasien tidak terdaftar di SATUSEHAT.'); // FIX 1
        }

        $ihsNumber = $response->json()['entry'][0]['resource']['id'];
        $nama = $response->json()['entry'][0]['resource']['name'][0]['text'] ?? null; // FIX 3
        
        IhsLookup::create([
            'nik' => $nik,
            'tipe' => 'pasien',
            'ihs_number' => $ihsNumber,
            'nama' => $nama, // FIX 3
            'ditemukan' => true
        ]);

        return [
            'success' => true,
            'ihs_number' => $ihsNumber,
            'nama' => $nama, // FIX 3
        ];
    }

    public function getPractitionerIhs(string $nik): array
    {
        $cached = IhsLookup::where('nik', $nik)->where('tipe', 'dokter')->first();
        if ($cached) {
            if (!$cached->ditemukan) {
                throw new \Exception('NIK Dokter tidak terdaftar di SATUSEHAT.'); // FIX 1
            }
            return [
                'success' => true,
                'ihs_number' => $cached->ihs_number,
                'nama' => $cached->nama, // FIX 3
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
            IhsLookup::create(['nik' => $nik, 'tipe' => 'dokter', 'ditemukan' => false]);
            throw new \Exception('NIK Dokter tidak terdaftar di SATUSEHAT.'); // FIX 1
        }

        $ihsNumber = $response->json()['entry'][0]['resource']['id'];
        $nama = $response->json()['entry'][0]['resource']['name'][0]['text'] ?? null; // FIX 3

        IhsLookup::create([
            'nik' => $nik,
            'tipe' => 'dokter',
            'ihs_number' => $ihsNumber,
            'nama' => $nama, // FIX 3
            'ditemukan' => true
        ]);

        return [
            'success' => true,
            'ihs_number' => $ihsNumber,
            'nama' => $nama, // FIX 3
        ];
    }

    public function getPatientIhsByNik(string $nik): array
    {
        return $this->getPatientIhs($nik);
    }

    public function getPractitionerIhsByNik(string $nik): array
    {
        return $this->getPractitionerIhs($nik);
    }
}