<?php

namespace Tests\Unit;

use App\Services\MasterDataService;
use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MasterDataServiceTest extends TestCase
{
    use WithFaker;

    public function test_get_patient_ihs_by_nik_success_returns_p_identifier()
    {
        Http::fake([
            '*/Patient*' => Http::response([
                'entry' => [
                    [
                        'resource' => [
                            'identifier' => [
                                ['value' => 'X123'],
                                ['value' => 'P1234567890'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new MasterDataService();

        $ihs = $service->getPatientIhsByNik('1000000000000001');

        $this->assertSame('P1234567890', $ihs);
    }

    public function test_get_patient_ihs_by_nik_missing_identifier_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('IHS Number Pasien tidak ditemukan pada response SATUSEHAT.');

        Http::fake([
            '*/Patient*' => Http::response([
                'entry' => [
                    [
                        'resource' => [
                            'identifier' => [
                                ['value' => 'X123'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new MasterDataService();

        $service->getPatientIhsByNik('1000000000000001');
    }

    public function test_get_patient_ihs_by_nik_404_throws_not_registered_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('NIK tidak terdaftar di SATUSEHAT');

        Http::fake([
            '*/Patient*' => Http::response([], 404),
        ]);

        $service = new MasterDataService();

        $service->getPatientIhsByNik('1000000000000001');
    }

    public function test_get_patient_ihs_by_nik_500_throws_generic_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Gagal mengambil data pasien dari SATUSEHAT.');

        Http::fake([
            '*/Patient*' => Http::response([], 500),
        ]);

        $service = new MasterDataService();

        $service->getPatientIhsByNik('1000000000000001');
    }

    public function test_get_patient_ihs_by_nik_invalid_json_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('IHS Number Pasien tidak ditemukan pada response SATUSEHAT.');

        Http::fake([
            '*/Patient*' => Http::response(null, 200),
        ]);

        $service = new MasterDataService();

        $service->getPatientIhsByNik('1000000000000001');
    }

    public function test_get_practitioner_ihs_by_nik_success_returns_n_identifier()
    {
        Http::fake([
            '*/Practitioner*' => Http::response([
                'entry' => [
                    [
                        'resource' => [
                            'identifier' => [
                                ['value' => 'X999'],
                                ['value' => 'N9876543210'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new MasterDataService();

        $ihs = $service->getPractitionerIhsByNik('1000000000000002');

        $this->assertSame('N9876543210', $ihs);
    }

    public function test_get_practitioner_ihs_by_nik_success_prefers_dr_identifier()
    {
        Http::fake([
            '*/Practitioner*' => Http::response([
                'entry' => [
                    [
                        'resource' => [
                            'identifier' => [
                                ['value' => 'DR0123456789'],
                                ['value' => 'N0000000000'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new MasterDataService();

        $ihs = $service->getPractitionerIhsByNik('1000000000000002');

        $this->assertSame('DR0123456789', $ihs);
    }

    public function test_get_practitioner_ihs_by_nik_missing_identifier_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('IHS Number Dokter tidak ditemukan pada response SATUSEHAT.');

        Http::fake([
            '*/Practitioner*' => Http::response([
                'entry' => [
                    [
                        'resource' => [
                            'identifier' => [
                                ['value' => 'X999'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new MasterDataService();

        $service->getPractitionerIhsByNik('1000000000000002');
    }

    public function test_get_practitioner_ihs_by_nik_404_throws_not_registered_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('NIK tidak terdaftar di SATUSEHAT');

        Http::fake([
            '*/Practitioner*' => Http::response([], 404),
        ]);

        $service = new MasterDataService();

        $service->getPractitionerIhsByNik('1000000000000002');
    }

    public function test_get_practitioner_ihs_by_nik_500_throws_generic_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Gagal mengambil data dokter dari SATUSEHAT.');

        Http::fake([
            '*/Practitioner*' => Http::response([], 500),
        ]);

        $service = new MasterDataService();

        $service->getPractitionerIhsByNik('1000000000000002');
    }

    public function test_get_practitioner_ihs_by_nik_invalid_json_throws_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('IHS Number Dokter tidak ditemukan pada response SATUSEHAT.');

        Http::fake([
            '*/Practitioner*' => Http::response(null, 200),
        ]);

        $service = new MasterDataService();

        $service->getPractitionerIhsByNik('1000000000000002');
    }
}