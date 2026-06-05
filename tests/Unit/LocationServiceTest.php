<?php

namespace Tests\Unit;

use App\Models\Location;
use App\Services\AuthService;
use App\Services\LocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): LocationService
    {
        $auth = \Mockery::mock(AuthService::class);
        $auth->shouldReceive('getAccessToken')->andReturn('fake-token');
        $auth->shouldReceive('forceNewToken')->andReturn('fake-token-refreshed');

        return new LocationService($auth);
    }

    /**
     * Test 1 — Happy path: POST Location berhasil
     */
    public function test_create_location_success(): void
    {
        Http::fake([
            '*/Location*' => Http::response([
                'id'           => 'fc0befd0-08e3-414d-85c6-0810c642306d',
                'resourceType' => 'Location',
                'status'       => 'active',
                'name'         => 'Ruang Poli Umum',
            ], 201),
        ]);

        $service = $this->makeService();
        $result  = $service->getOrCreateLocation();

        $this->assertTrue($result['success']);
        $this->assertEquals('fc0befd0-08e3-414d-85c6-0810c642306d', $result['location_id']);

        // Pastikan tersimpan di DB
        $this->assertDatabaseHas('locations', [
            'nama_ruangan'          => 'Ruang Poli Umum',
            'location_id_satusehat' => 'fc0befd0-08e3-414d-85c6-0810c642306d',
        ]);
    }

    /**
     * Test 2 — Local first: ambil dari DB, tidak POST ke SATUSEHAT
     */
    public function test_get_location_from_local_db(): void
    {
        Location::create([
            'nama_ruangan'          => 'Ruang Poli Umum',
            'location_id_satusehat' => 'fc0befd0-08e3-414d-85c6-0810c642306d',
            'org_id'                => '7a9814fe-48ea-4dd7-9d1b-9289d9aecbca',
        ]);

        $service = $this->makeService();
        $result  = $service->getOrCreateLocation();

        $this->assertTrue($result['success']);
        $this->assertEquals('Location sudah ada di database', $result['message']);
        $this->assertEquals('fc0befd0-08e3-414d-85c6-0810c642306d', $result['location_id']);

        // Tidak boleh ada request ke SATUSEHAT
        Http::assertNothingSent();
    }

    /**
     * Test 3 — Negative: SATUSEHAT return 400
     */
    public function test_create_location_fails_on_400(): void
    {
        Http::fake([
            '*/Location*' => Http::response([
                'resourceType' => 'OperationOutcome',
                'issue'        => [['severity' => 'error', 'code' => 'value']],
            ], 400),
        ]);

        $service = $this->makeService();
        $result  = $service->getOrCreateLocation();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('400', $result['message']);

        // Pastikan tidak tersimpan di DB
        $this->assertDatabaseEmpty('locations');
    }

    /**
     * Test 4 — Auto-refresh token saat 401
     */
    public function test_create_location_refreshes_token_on_401(): void
    {
        Http::fake([
            '*/Location*' => Http::sequence()
                ->push(['fault' => 'unauthorized'], 401)
                ->push([
                    'id'           => 'new-location-id',
                    'resourceType' => 'Location',
                    'status'       => 'active',
                ], 201),
        ]);

        $service = $this->makeService();
        $result  = $service->getOrCreateLocation();

        $this->assertTrue($result['success']);
        $this->assertEquals('new-location-id', $result['location_id']);
    }
}