<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class AuthService
{
    protected $authUrl;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->authUrl = env('SATUSEHAT_AUTH_URL');
        $this->clientId = env('SATUSEHAT_CLIENT_ID');
        $this->clientSecret = env('SATUSEHAT_CLIENT_SECRET');
    }

    /**
     * Mendapatkan Access Token dengan proteksi Cache & Error Handling 401
     */
    public function getAccessToken(): string
    {
        // Kriteria: Token disimpan di cache selama 50 menit (~1 jam) agar tidak di-request berulang kali
        return Cache::remember('satusehat_access_token', 3000, function () {
            return $this->requestNewToken();
        });
    }

    /**
     * Fungsi murni untuk menembak API OAuth2 Kemenkes
     */
    private function requestNewToken(): string
    {
        $url = $this->authUrl . "/accesstoken?grant_type=client_credentials";

        // Kriteria: Request Http::asForm() dengan Content-Type: application/x-www-form-urlencoded
        $response = Http::asForm()->post($url, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        // Kriteria: Handle 401 & Exception Informatif jika client_id/secret salah
        if ($response->status() === 401 || $response->failed()) {
            throw new Exception("Gagal Otentikasi SATUSEHAT: Kombinasi Client ID atau Client Secret di .env salah/tidak sah.");
        }

        return $response->json()['access_token'];
    }

    /**
     * Kriteria Error Handling 401: Jika token di cache ternyata hangus di server Kemenkes,
     * hapus cache lama dan paksa ambil token baru otomatis.
     */
    public function forceNewToken(): string
    {
        Cache::forget('satusehat_access_token');
        return $this->getAccessToken();
    }
}