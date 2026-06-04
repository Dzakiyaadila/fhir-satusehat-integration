<?php

namespace App\Services;

use App\Models\Encounter;
use Illuminate\Support\Facades\Http;

class EncounterService
{
    protected string $baseUrl;
    protected string $orgId;

    public function __construct(
        private AuthService       $authService,
        private MasterDataService $masterDataService,
        private LocationService   $locationService,
    ) {
        $this->baseUrl = env('SATUSEHAT_BASE_URL');
        $this->orgId   = env('SATUSEHAT_ORG_ID');
    }

    // =========================================================================
    // PUBLIC: POST Encounter (status: arrived)
    // =========================================================================

    /**
     * Daftarkan kunjungan pasien baru ke SATUSEHAT.
     *
     * @param  string $nikPasien  NIK KTP pasien (16 digit)
     * @param  string $nikDokter  NIK KTP dokter (16 digit)
     * @return array  ['success', 'message', 'encounter_id'?, 'data'?]
     */
    public function createEncounter(string $nikPasien, string $nikDokter): array
    {
        // ------------------------------------------------------------------
        // 1. Ambil IHS pasien — MasterDataService throw Exception jika gagal
        // ------------------------------------------------------------------
        try {
            $pasienResult = $this->masterDataService->getPatientIhsByNik($nikPasien);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $ihsPasien  = $pasienResult['ihs_number'];
        $namaPasien = $pasienResult['nama'];

        // ------------------------------------------------------------------
        // 2. Ambil IHS dokter
        // ------------------------------------------------------------------
        try {
            $dokterResult = $this->masterDataService->getPractitionerIhsByNik($nikDokter);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $ihsDokter  = $dokterResult['ihs_number'];
        $namaDokter = $dokterResult['nama'];

        // ------------------------------------------------------------------
        // 3. Ambil Location ID
        // ------------------------------------------------------------------
        $locationResult = $this->locationService->getOrCreateLocation();

        if (! $locationResult['success']) {
            return ['success' => false, 'message' => 'Location tidak tersedia: ' . $locationResult['message']];
        }

        $locationId = $locationResult['location_id'];

        // ------------------------------------------------------------------
        // 4. Simpan record lokal dulu (encounter_id_satusehat masih null)
        // ------------------------------------------------------------------
        $waktuKunjungan = now()->timezone('UTC')->toIso8601String();
        $nomorInternal  = $this->generateNomorInternal();

        $encounter = Encounter::create([
            'nik_pasien'            => $nikPasien,
            'ihs_pasien'            => $ihsPasien,
            'nama_pasien'           => $namaPasien,
            'ihs_dokter'            => $ihsDokter,
            'nama_dokter'           => $namaDokter,
            'location_id_satusehat' => $locationId,
            'nomor_internal'        => $nomorInternal,
            'waktu_kunjungan'       => $waktuKunjungan,
            'status'                => 'arrived',
        ]);

        // ------------------------------------------------------------------
        // 5. POST ke SATUSEHAT
        // ------------------------------------------------------------------
        $payload = $this->buildEncounterPayload(
            encounterId:    null,
            ihsPasien:      $ihsPasien,
            namaPasien:     $namaPasien,
            ihsDokter:      $ihsDokter,
            namaDokter:     $namaDokter,
            locationId:     $locationId,
            nomorInternal:  $nomorInternal,
            waktuKunjungan: $waktuKunjungan,
            status:         'arrived',
            statusHistory:  [
                ['status' => 'arrived', 'period' => ['start' => $waktuKunjungan]],
            ],
        );

        $token    = $this->authService->getAccessToken();
        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl . '/Encounter', $payload);

        if ($response->status() === 401) {
            $token    = $this->authService->forceNewToken();
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . '/Encounter', $payload);
        }

        // ------------------------------------------------------------------
        // 6. Handle response
        // ------------------------------------------------------------------
        if ($response->status() === 201) {
            $responseData = $response->json();
            $encounterId  = $responseData['id'];

            $encounter->update(['encounter_id_satusehat' => $encounterId]);

            return [
                'success'            => true,
                'message'            => 'Encounter berhasil dibuat (arrived)',
                'encounter_id'       => $encounterId,
                'nomor_internal'     => $nomorInternal,
                'data'               => $encounter->fresh(),
                'satusehat_response' => $responseData,
            ];
        }

        // Gagal — rollback record lokal agar tidak orphan
        $encounter->delete();

        return [
            'success'     => false,
            'message'     => $this->humanErrorMessage($response->status()),
            'http_status' => $response->status(),
            'response'    => $response->json(),
        ];
    }

    // =========================================================================
    // PUBLIC: PUT Encounter (status: in-progress)
    // =========================================================================

    /**
     * Update status encounter menjadi in-progress (pasien masuk ruang periksa).
     *
     * @param  string $encounterId  UUID Encounter ID dari SATUSEHAT
     * @return array  ['success', 'message', 'data'?]
     */
    public function updateToInProgress(string $encounterId): array
    {
        // ------------------------------------------------------------------
        // 1. Cari encounter di DB lokal
        // ------------------------------------------------------------------
        $encounter = Encounter::where('encounter_id_satusehat', $encounterId)->first();

        if (! $encounter) {
            return ['success' => false, 'message' => 'Encounter tidak ditemukan di database lokal.'];
        }

        if ($encounter->status === 'in-progress') {
            return ['success' => false, 'message' => 'Encounter sudah berstatus in-progress.'];
        }

        // ------------------------------------------------------------------
        // 2. Waktu masuk ruang = sekarang UTC+0
        // ------------------------------------------------------------------
        $waktuMasukRuang = now()->timezone('UTC')->toIso8601String();

        // ------------------------------------------------------------------
        // 3. Bangun payload PUT
        // ------------------------------------------------------------------
        $payload = $this->buildEncounterPayload(
            encounterId:    $encounterId,
            ihsPasien:      $encounter->ihs_pasien,
            namaPasien:     $encounter->nama_pasien,
            ihsDokter:      $encounter->ihs_dokter,
            namaDokter:     $encounter->nama_dokter,
            locationId:     $encounter->location_id_satusehat,
            nomorInternal:  $encounter->nomor_internal,
            waktuKunjungan: $encounter->waktu_kunjungan,
            status:         'in-progress',
            statusHistory:  [
                [
                    'status' => 'arrived',
                    'period' => ['start' => $encounter->waktu_kunjungan, 'end' => $waktuMasukRuang],
                ],
                [
                    'status' => 'in-progress',
                    'period' => ['start' => $waktuMasukRuang],
                ],
            ],
        );

        // ------------------------------------------------------------------
        // 4. Kirim PUT ke SATUSEHAT
        // ------------------------------------------------------------------
        $token    = $this->authService->getAccessToken();
        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->put($this->baseUrl . '/Encounter/' . $encounterId, $payload);

        if ($response->status() === 401) {
            $token    = $this->authService->forceNewToken();
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->put($this->baseUrl . '/Encounter/' . $encounterId, $payload);
        }

        // ------------------------------------------------------------------
        // 5. Handle response (PUT sukses = 200 OK)
        // ------------------------------------------------------------------
        if ($response->status() === 200) {
            $encounter->update([
                'status'           => 'in-progress',
                'waktu_masuk_ruang' => $waktuMasukRuang,
            ]);

            return [
                'success'            => true,
                'message'            => 'Encounter berhasil diupdate ke in-progress',
                'encounter_id'       => $encounterId,
                'data'               => $encounter->fresh(),
                'satusehat_response' => $response->json(),
            ];
        }

        return [
            'success'     => false,
            'message'     => $this->humanErrorMessage($response->status()),
            'http_status' => $response->status(),
            'response'    => $response->json(),
        ];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Bangun payload FHIR R4 Encounter.
     * POST → $encounterId = null (tidak ada "id" di root)
     * PUT  → $encounterId = UUID (wajib ada "id" di root)
     */
    private function buildEncounterPayload(
        ?string $encounterId,
        string  $ihsPasien,
        string  $namaPasien,
        string  $ihsDokter,
        string  $namaDokter,
        string  $locationId,
        string  $nomorInternal,
        string  $waktuKunjungan,
        string  $status,
        array   $statusHistory,
    ): array {
        $payload = [
            'resourceType' => 'Encounter',
            'status'       => $status,
            'class'        => [
                'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code'    => 'AMB',
                'display' => 'ambulatory',
            ],
            'serviceType' => [
                'coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '419192003',
                    'display' => 'Internal medicine',
                ]],
            ],
            'identifier' => [[
                'system' => 'http://sys-ids.kemkes.go.id/encounter/' . $this->orgId,
                'value'  => $nomorInternal,
            ]],
            'subject' => [
                'reference' => 'Patient/' . $ihsPasien,
                'display'   => $namaPasien,
            ],
            'participant' => [[
                'type' => [[
                    'coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                        'code'    => 'ATND',
                        'display' => 'attender',
                    ]],
                ]],
                'individual' => [
                    'reference' => 'Practitioner/' . $ihsDokter,
                    'display'   => $namaDokter,
                ],
            ]],
            'period' => ['start' => $waktuKunjungan],
            'location' => [[
                'extension' => [[
                    'url'       => 'https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass',
                    'extension' => [
                        [
                            'url'                  => 'value',
                            'valueCodeableConcept'  => [
                                'coding' => [[
                                    'system'  => 'http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient',
                                    'code'    => 'reguler',
                                    'display' => 'Kelas Reguler',
                                ]],
                            ],
                        ],
                        [
                            'url'                  => 'upgradeClassIndicator',
                            'valueCodeableConcept'  => [
                                'coding' => [[
                                    'system'  => 'http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass',
                                    'code'    => 'kelas-tetap',
                                    'display' => 'Kelas Tetap Perawatan',
                                ]],
                            ],
                        ],
                    ],
                ]],
                'location' => [
                    'reference' => 'Location/' . $locationId,
                    'display'   => 'Ruang Poli Umum',
                ],
                'period' => ['start' => $waktuKunjungan],
            ]],
            'statusHistory'   => $statusHistory,
            'serviceProvider' => ['reference' => 'Organization/' . $this->orgId],
        ];

        // PUT wajib menyertakan "id" di root payload
        if ($encounterId !== null) {
            $payload['id'] = $encounterId;
        }

        return $payload;
    }

    /**
     * Nomor internal format ENC-YYYYMMDD-001, sequence reset tiap hari.
     */
    private function generateNomorInternal(): string
    {
        $today      = now()->format('Ymd');
        $countToday = Encounter::whereDate('created_at', now()->toDateString())->count();
        $sequence   = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        return 'ENC-' . $today . '-' . $sequence;
    }

    /**
     * Pesan error ramah untuk ditampilkan ke user.
     */
    private function humanErrorMessage(int $httpStatus): string
    {
        return match ($httpStatus) {
            401 => 'Sesi telah berakhir. Sistem sedang memperbarui akses.',
            404 => 'Data NIK tidak ditemukan di SATUSEHAT. Periksa kembali NIK yang dimasukkan.',
            400 => 'Format data tidak valid. Hubungi tim teknis.',
            422 => 'Validasi FHIR gagal. Periksa kelengkapan data.',
            500 => 'Layanan SATUSEHAT sedang gangguan. Coba beberapa saat lagi.',
            default => 'Terjadi error tidak terduga. HTTP ' . $httpStatus,
        };
    }
}