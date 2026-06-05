<?php

namespace Tests\Unit;

use App\Models\IhsLookup;
use App\Services\AuthService;
use App\Services\MasterDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MasterDataServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): MasterDataService
    {
        // Mock AuthService agar tidak hit SATUSEHAT asli
        $auth = \Mockery::mock(AuthService::class);
        $auth->shouldReceive('getAccessToken')->andReturn('fake-token');
        $auth->shouldReceive('forceNewToken')->andReturn('fake-token-refreshed');

        return new MasterDataService($auth);
    }

    /**
     * Test 1 — Happy path: berhasil dapat IHS Number pasien
     */
    public function test_get_patient_ihs_success(): void
    {
        Http::fake([
            '*/Patient*' => Http::response([
                'resourceType' => 'Bundle',
                'total'        => 1,
                'entry'        => [
                    [
                        'resource' => [
                            'id'           => 'P02478375538',
                            'resourceType' => 'Patient',
                            'name'         => [['text' => 'Budi Santoso']],
                            'meta'         => ['lastUpdated' => '2025-01-01T00:00:00+00:00'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = $this->makeService();
        $result  = $service->getPatientIhs('9271060312000001');

        $this->assertTrue($result['success']);
        $this->assertEquals('P02478375538', $result['ihs_number']);
        $this->assertEquals('Budi Santoso', $result['nama']);
    }

    /**
     * Test 2 — Happy path: berhasil dapat IHS Number dokter
     */
    public function test_get_practitioner_ihs_success(): void
    {
        Http::fake([
            '*/Practitioner*' => Http::response([
                'resourceType' => 'Bundle',
                'total'        => 1,
                'entry'        => [
                    [
                        'resource' => [
                            'id'           => '10009880728',
                            'resourceType' => 'Practitioner',
                            'name'         => [['text' => 'dr. Budi']],
                            'meta'         => ['lastUpdated' => '2025-01-01T00:00:00+00:00'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = $this->makeService();
        $result  = $service->getPractitionerIhs('1000000000000002');

        $this->assertTrue($result['success']);
        $this->assertEquals('10009880728', $result['ihs_number']);
        $this->assertEquals('dr. Budi', $result['nama']);
    }

    /**
     * Test 3 — Local first: data dari DB lokal, tidak hit API
     */
    public function test_get_patient_ihs_from_local_db(): void
    {
        // Siapkan data di DB lokal
        IhsLookup::create([
            'nik'        => '9271060312000001',
            'tipe'       => 'pasien',
            'ihs_number' => 'P02478375538',
            'nama'       => 'Budi Santoso',
            'ditemukan'  => true,
        ]);

        $service = $this->makeService();
        $result  = $service->getPatientIhs('9271060312000001');

        $this->assertTrue($result['success']);
        $this->assertEquals('P02478375538', $result['ihs_number']);

        // Tidak boleh ada request ke SATUSEHAT
        Http::assertNothingSent();
    }

    /**
     * Test 4 — Negative: NIK pasien tidak ditemukan → throw Exception
     */
    public function test_get_patient_ihs_throws_exception_when_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('NIK Pasien tidak terdaftar di SATUSEHAT.');

        Http::fake([
            '*/Patient*' => Http::response([
                'resourceType' => 'Bundle',
                'total'        => 0,
                'entry'        => [],
            ], 200),
        ]);

        $service = $this->makeService();
        $service->getPatientIhs('9999999999999999');
    }

    /**
     * Test 5 — Negative: NIK dokter tidak ditemukan → throw Exception
     */
    public function test_get_practitioner_ihs_throws_exception_when_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('NIK Dokter tidak terdaftar di SATUSEHAT.');

        Http::fake([
            '*/Practitioner*' => Http::response([
                'resourceType' => 'Bundle',
                'total'        => 0,
                'entry'        => [],
            ], 200),
        ]);

        $service = $this->makeService();
        $service->getPractitionerIhs('9999999999999999');
    }

    /**
     * Test 6 — Negative: NIK sudah di-cache sebagai "tidak ditemukan"
     */
    public function test_get_patient_ihs_throws_exception_from_cached_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('NIK Pasien tidak terdaftar di SATUSEHAT.');

        IhsLookup::create([
            'nik'       => '9999999999999999',
            'tipe'      => 'pasien',
            'ditemukan' => false,
        ]);

        $service = $this->makeService();
        $service->getPatientIhs('9999999999999999');

        // Tidak boleh ada request ke SATUSEHAT
        Http::assertNothingSent();
    }
}