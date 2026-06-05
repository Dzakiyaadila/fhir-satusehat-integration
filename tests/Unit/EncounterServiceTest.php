<?php

namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use App\Models\Encounter;
use App\Services\AuthService;
use App\Services\LocationService;
use App\Services\MasterDataService;
use App\Services\EncounterService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EncounterServiceTest extends TestCase
{
    use RefreshDatabase;

protected $authService;
protected $masterDataService;
protected $locationService;
protected EncounterService $encounterService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = Mockery::mock(AuthService::class);
        $this->masterDataService = Mockery::mock(MasterDataService::class);
        $this->locationService = Mockery::mock(LocationService::class);

        $this->encounterService = new EncounterService(
            $this->authService,
            $this->masterDataService,
            $this->locationService
        );
    }

    public function test_create_encounter_success(): void
    {
        $this->masterDataService
            ->shouldReceive('getPatientIhsByNik')
            ->once()
            ->andReturn([
                'ihs_number' => 'PATIENT-001',
                'nama' => 'Budi'
            ]);

        $this->masterDataService
            ->shouldReceive('getPractitionerIhsByNik')
            ->once()
            ->andReturn([
                'ihs_number' => 'DOCTOR-001',
                'nama' => 'Dr Andi'
            ]);

        $this->locationService
            ->shouldReceive('getOrCreateLocation')
            ->once()
            ->andReturn([
                'success' => true,
                'location_id' => 'LOC-001'
            ]);

        $this->authService
            ->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('fake-token');

        Http::fake([
            '*' => Http::response([
                'id' => 'ENC-001'
            ], 201)
        ]);

        $result = $this->encounterService->createEncounter(
            '1234567890123456',
            '9876543210987654'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('ENC-001', $result['encounter_id']);
    }

    public function test_create_encounter_refresh_token_on_401(): void
    {
        $this->masterDataService
            ->shouldReceive('getPatientIhsByNik')
            ->andReturn([
                'ihs_number' => 'PATIENT-001',
                'nama' => 'Budi'
            ]);

        $this->masterDataService
            ->shouldReceive('getPractitionerIhsByNik')
            ->andReturn([
                'ihs_number' => 'DOCTOR-001',
                'nama' => 'Dr Andi'
            ]);

        $this->locationService
            ->shouldReceive('getOrCreateLocation')
            ->andReturn([
                'success' => true,
                'location_id' => 'LOC-001'
            ]);

        $this->authService
            ->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('expired-token');

        $this->authService
            ->shouldReceive('forceNewToken')
            ->once()
            ->andReturn('new-token');

        Http::fake([
            '*' => Http::sequence()
                ->push([], 401)
                ->push(['id' => 'ENC-002'], 201)
        ]);

        $result = $this->encounterService->createEncounter(
            '1234567890123456',
            '9876543210987654'
        );

        $this->assertTrue($result['success']);
    }

    public function test_create_encounter_failed_and_rollback(): void
    {
        $this->masterDataService
            ->shouldReceive('getPatientIhsByNik')
            ->andReturn([
                'ihs_number' => 'PATIENT-001',
                'nama' => 'Budi'
            ]);

        $this->masterDataService
            ->shouldReceive('getPractitionerIhsByNik')
            ->andReturn([
                'ihs_number' => 'DOCTOR-001',
                'nama' => 'Dr Andi'
            ]);

        $this->locationService
            ->shouldReceive('getOrCreateLocation')
            ->andReturn([
                'success' => true,
                'location_id' => 'LOC-001'
            ]);

        $this->authService
            ->shouldReceive('getAccessToken')
            ->andReturn('fake-token');

        Http::fake([
            '*' => Http::response([
                'error' => 'failed'
            ], 400)
        ]);

        $result = $this->encounterService->createEncounter(
            '1234567890123456',
            '9876543210987654'
        );

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('encounters', 0);
    }

    public function test_update_encounter_to_in_progress_success(): void
    {
        $encounter = Encounter::create([
            'nik_pasien' => '123',
            'ihs_pasien' => 'PATIENT-001',
            'nama_pasien' => 'Budi',
            'ihs_dokter' => 'DOCTOR-001',
            'nama_dokter' => 'Dr Andi',
            'location_id_satusehat' => 'LOC-001',
            'encounter_id_satusehat' => 'ENC-001',
            'nomor_internal' => 'ENC-20260605-001',
            'waktu_kunjungan' => now()->toIso8601String(),
            'status' => 'arrived',
        ]);

        $this->authService
            ->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('fake-token');

        Http::fake([
            '*' => Http::response([
                'id' => 'ENC-001'
            ], 200)
        ]);

        $result = $this->encounterService
            ->updateToInProgress('ENC-001');

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('encounters', [
            'encounter_id_satusehat' => 'ENC-001',
            'status' => 'in-progress'
        ]);
    }

    public function test_update_encounter_not_found(): void
    {
        $result = $this->encounterService
            ->updateToInProgress('NOT-FOUND');

        $this->assertFalse($result['success']);
    }
}