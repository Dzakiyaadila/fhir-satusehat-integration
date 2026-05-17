<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class MasterDataService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.satusehat.base_url', ''), '/');
    }

    /**
     * Ambil IHS Number Pasien berdasarkan NIK.
     *
     * @param string $nik
     * @return string
     *
     * @throws Exception
     */
    public function getPatientIhsByNik(string $nik = '1000000000000001'): string
    {
        if (empty($this->baseUrl)) {
            throw new Exception('SATUSEHAT base URL is not configured.');
        }

        $url = $this->baseUrl . '/Patient';

        try {
            $response = Http::get($url, [
                'identifier' => $nik,
            ]);
        } catch (Exception $e) {
            throw new Exception('Gagal menghubungi layanan SATUSEHAT.');
        }

        if ($response->status() === 404) {
            throw new Exception('NIK tidak terdaftar di SATUSEHAT');
        }

        if ($response->failed()) {
            throw new Exception('Gagal mengambil data pasien dari SATUSEHAT.');
        }

        $data = $response->json();

        // Asumsi format response FHIR Patient bundle
        // dan IHS Number ada di identifier yang diawali huruf 'P'
        if (isset($data['entry']) && is_array($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                $resource = $entry['resource'] ?? [];
                if (!isset($resource['identifier']) || !is_array($resource['identifier'])) {
                    continue;
                }

                foreach ($resource['identifier'] as $identifier) {
                    $value = $identifier['value'] ?? null;
                    if (is_string($value) && str_starts_with($value, 'P')) {
                        return $value;
                    }
                }
            }
        }

        throw new Exception('IHS Number Pasien tidak ditemukan pada response SATUSEHAT.');
    }

    /**
     * Ambil IHS Number Dokter berdasarkan NIK.
     *
     * @param string $nik
     * @return string
     *
     * @throws Exception
     */
    public function getPractitionerIhsByNik(string $nik = '1000000000000002'): string
    {
        if (empty($this->baseUrl)) {
            throw new Exception('SATUSEHAT base URL is not configured.');
        }

        $url = $this->baseUrl . '/Practitioner';

        try {
            $response = Http::get($url, [
                'identifier' => $nik,
            ]);
        } catch (Exception $e) {
            throw new Exception('Gagal menghubungi layanan SATUSEHAT.');
        }

        if ($response->status() === 404) {
            throw new Exception('NIK tidak terdaftar di SATUSEHAT');
        }

        if ($response->failed()) {
            throw new Exception('Gagal mengambil data dokter dari SATUSEHAT.');
        }

        $data = $response->json();

        // Asumsi format response FHIR Practitioner bundle
        // dan IHS Number ada di identifier yang diawali huruf 'N' atau 'DR'
        if (isset($data['entry']) && is_array($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                $resource = $entry['resource'] ?? [];
                if (!isset($resource['identifier']) || !is_array($resource['identifier'])) {
                    continue;
                }

                foreach ($resource['identifier'] as $identifier) {
                    $value = $identifier['value'] ?? null;
                    if (!is_string($value)) {
                        continue;
                    }

                    if (str_starts_with($value, 'N') || str_starts_with($value, 'DR')) {
                        return $value;
                    }
                }
            }
        }

        throw new Exception('IHS Number Dokter tidak ditemukan pada response SATUSEHAT.');
    }
}