<?php

namespace Tests\Unit;

use App\Services\AuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    /**
     * Test 1 — Happy path: berhasil dapat token
     */
    public function test_get_access_token_success(): void
    {
        Http::fake([
            '*/accesstoken*' => Http::response([
                'access_token' => 'fake-token-xyz',
                'token_type'   => 'BearerToken',
                'expires_in'   => '14399',
            ], 200),
        ]);

        $service = new AuthService();
        $token   = $service->getAccessToken();

        $this->assertEquals('fake-token-xyz', $token);
    }

    /**
     * Test 2 — Token di-cache, tidak request ulang
     */
    public function test_token_is_cached_and_not_requested_twice(): void
    {
        Http::fake([
            '*/accesstoken*' => Http::response([
                'access_token' => 'cached-token',
                'token_type'   => 'BearerToken',
                'expires_in'   => '14399',
            ], 200),
        ]);

        $service = new AuthService();

        $token1 = $service->getAccessToken();
        $token2 = $service->getAccessToken();

        // Keduanya harus sama — dari cache
        $this->assertEquals($token1, $token2);

        // HTTP hanya dipanggil sekali
        Http::assertSentCount(1);
    }

    /**
     * Test 3 — forceNewToken menghapus cache dan minta token baru
     */
    public function test_force_new_token_clears_cache(): void
    {
        Http::fake([
            '*/accesstoken*' => Http::sequence()
                ->push(['access_token' => 'token-lama', 'expires_in' => '14399'], 200)
                ->push(['access_token' => 'token-baru', 'expires_in' => '14399'], 200),
        ]);

        $service    = new AuthService();
        $tokenLama  = $service->getAccessToken();
        $tokenBaru  = $service->forceNewToken();

        $this->assertEquals('token-lama', $tokenLama);
        $this->assertEquals('token-baru', $tokenBaru);
    }

    /**
     * Test 4 — Negative: credentials salah → throw Exception
     */
    public function test_get_access_token_throws_exception_on_401(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Gagal Otentikasi SATUSEHAT');

        Http::fake([
            '*/accesstoken*' => Http::response([
                'resourceType' => 'OperationOutcome',
            ], 401),
        ]);

        Cache::forget('satusehat_access_token');

        $service = new AuthService();
        $service->getAccessToken();
    }
}
