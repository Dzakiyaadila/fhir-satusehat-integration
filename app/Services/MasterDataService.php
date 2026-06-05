<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\IhsLookup;
use App\Models\IntegrationLog;

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
                throw new \Exception('NIK Pasien tidak terdaftar di SATUSEHAT.');
            }
            return [
                'success' => true,
                'ihs_number' => $cached->ihs_number,
                'nama' => $cached->nama,
            ];
        }

        $token = $this->authService->getAccessToken();
        
        $params = ['identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik];
        $response = Http::withToken($token)->get($this->baseUrl . "/Patient", $params);

        if ($response->status() === 401) {
            $newToken = $this->authService->forceNewToken();
            $response = Http::withToken($newToken)->get($this->baseUrl . "/Patient", $params);
        }

        // CATAT LOG INTEGRASI (SUKSES MAUPUN GAGAL)
        IntegrationLog::create([
            'step'             => 'get_patient_ihs',
            'http_status'      => $response->status(),
            'request_payload'  => $params,
            'response_payload' => $response->json(),
            'error_message'    => $response->status() === 200 && !empty($response->json()['entry']) ? null : 'NIK Pasien tidak ditemukan atau response error dari SATUSEHAT.',
        ]);

        if ($response->status() === 404 || empty($response->json()['entry'])) {
            IhsLookup::create(['nik' => $nik, 'tipe' => 'pasien', 'ditemukan' => false]);
            throw new \Exception('NIK Pasien tidak terdaftar di SATUSEHAT.');
        }

        $ihsNumber = $response->json()['entry'][0]['resource']['id'];
        $nama = $response->json()['entry'][0]['resource']['name'][0]['text'] ?? null;
        
        IhsLookup::create([
            'nik' => $nik,
            'tipe' => 'pasien',
            'ihs_number' => $ihsNumber,
            'nama' => $nama,
            'ditemukan' => true
        ]);

        return [
            'success' => true,
            'ihs_number' => $ihsNumber,
            'nama' => $nama,
        ];
    }

    public function getPractitionerIhs(string $nik): array
    {
        $cached = IhsLookup::where('nik', $nik)->where('tipe', 'dokter')->first();
        if ($cached) {
            if (!$cached->ditemukan) {
                throw new \Exception('NIK Dokter tidak terdaftar di SATUSEHAT.');
            }
            return [
                'success' => true,
                'ihs_number' => $cached->ihs_number,
                'nama' => $cached->nama,
            ];
        }

        $token = $this->authService->getAccessToken();

        $params = ['identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik];
        $response = Http::withToken($token)->get($this->baseUrl . "/Practitioner", $params);

        if ($response->status() === 401) {
            $newToken = $this->authService->forceNewToken();
            $response = Http::withToken($newToken)->get($this->baseUrl . "/Practitioner", $params);
        }

        // CATAT LOG INTEGRASI (SUKSES MAUPUN GAGAL)
        IntegrationLog::create([
            'step'             => 'get_practitioner_ihs',
            'http_status'      => $response->status(),
            'request_payload'  => $params,
            'response_payload' => $response->json(),
            'error_message'    => $response->status() === 200 && !empty($response->json()['entry']) ? null : 'NIK Dokter tidak ditemukan atau response error dari SATUSEHAT.',
        ]);

        if ($response->status() === 404 || empty($response->json()['entry'])) {
            IhsLookup::create(['nik' => $nik, 'tipe' => 'dokter', 'ditemukan' => false]);
            throw new \Exception('NIK Dokter tidak terdaftar di SATUSEHAT.');
        }

        $ihsNumber = $response->json()['entry'][0]['resource']['id'];
        $nama = $response->json()['entry'][0]['resource']['name'][0]['text'] ?? null;

        IhsLookup::create([
            'nik' => $nik,
            'tipe' => 'dokter',
            'ihs_number' => $ihsNumber,
            'nama' => $nama,
            'ditemukan' => true
        ]);

        return [
            'success' => true,
            'ihs_number' => $ihsNumber,
            'nama' => $nama,
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