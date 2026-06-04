<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SatusehatController extends Controller
{
    public function testSetup(): JsonResponse
    {
        return response()->json([
            'status' => 'OK',
            'message' => 'SATUSEHAT Integration API is running',
            'environment' => config('app.env'),
        ], 200);
    }

    // Stream 2: Auth (Simulasi)
    public function getToken(): JsonResponse
    {
        $responsePayload = ['access_token' => 'mock_token_12345', 'expires_in' => 3600];
        
        DB::table('integration_logs')->insert([
            'step' => 'AUTH',
            'http_status' => 200,
            'request_payload' => null,
            'response_payload' => json_encode($responsePayload),
            'created_at' => now(),
        ]);

        return response()->json($responsePayload, 200);
    }
    // Stream 3: IHS Number Patient
    public function getPatientIhs(string $nik): JsonResponse
    {
        $responsePayload = [
            'resourceType' => 'Patient', 
            'id' => 'P-99281', 
            'identifier' => [['value' => $nik]], 
            'name' => [['text' => 'Pasien Simulasi']]
        ];

        DB::table('integration_logs')->insert([
            'step' => 'PATIENT',
            'http_status' => 200,
            'request_payload' => json_encode(['nik' => $nik]),
            'response_payload' => json_encode($responsePayload),
            'created_at' => now(),
        ]);

        return response()->json($responsePayload, 200);
    }
    public function patient(Request $request): JsonResponse
    {
        $nik = $request->query('nik', '1000000000000001');
        $responsePayload = ['resourceType' => 'Patient', 'id' => 'P-99', 'identifier' => [['value' => $nik]]];
        DB::table('integration_logs')->insert([
            'step' => 'PATIENT',
            'http_status' => 200,
            'request_payload' => json_encode(['nik' => $nik]),
            'response_payload' => json_encode($responsePayload),
        ]);
        return response()->json($responsePayload, 200);
    }

    // Stream 3: IHS Practitioner (Menyesuaikan rute getPractitionerIhs)
    public function getPractitionerIhs(string $nik): JsonResponse
    {
        $responsePayload = [
            'resourceType' => 'Practitioner', 
            'id' => 'D-11029', 
            'identifier' => [['value' => $nik]], 
            'name' => [['text' => 'Dokter Simulasi']]
        ];

        DB::table('integration_logs')->insert([
            'step' => 'PRACTITIONER',
            'http_status' => 200,
            'request_payload' => json_encode(['nik' => $nik]),
            'response_payload' => json_encode($responsePayload),
            'created_at' => now(),
        ]);

        return response()->json($responsePayload, 200);
    }

    // Stream 4: Location (Simulasi)
    public function postLocation(Request $request): JsonResponse
    {
        $payload = $request->all();
        $responsePayload = [
            'resourceType' => 'Location', 
            'id' => 'LOC-5512', 
            'status' => 'active', 
            'name' => $payload['name'] ?? 'Ruang Poli Kandungan'
        ];

        DB::table('integration_logs')->insert([
            'step' => 'LOCATION',
            'http_status' => 201,
            'request_payload' => json_encode($payload),
            'response_payload' => json_encode($responsePayload),
            'created_at' => now(),
        ]);

        return response()->json($responsePayload, 201);
    }

    // Stream 5: Encounter (Simulasi)
    public function postEncounter(Request $request): JsonResponse
    {
        $payload = $request->all();
        $encounterId = 'ENC-' . rand(100000, 999999);
        $responsePayload = [
            'resourceType' => 'Encounter', 
            'id' => $encounterId, 
            'status' => 'arrived'
        ];

        DB::table('integration_logs')->insert([
            'encounter_id' => $encounterId,
            'step' => 'ENCOUNTER',
            'http_status' => 201,
            'request_payload' => json_encode($payload),
            'response_payload' => json_encode($responsePayload),
            'created_at' => now(),
        ]);

        return response()->json($responsePayload, 201);
    }

    public function updateToInProgress(Request $request, $id)
    {
        // Bersihkan ID jika ada karakter aneh seperti '){...}'
        $cleanId = str_replace([')', '{', '}'], '', $id);

        $responsePayload = [
            'resourceType' => 'Encounter',
            'id' => $cleanId,
            'status' => 'in-progress'
        ];

        // Simpan log ke database
        DB::table('integration_logs')->insert([
            'encounter_id' => $cleanId,
            'step' => 'ENCOUNTER_UPDATE',
            'http_status' => 200,
            'request_payload' => json_encode($request->all()),
            'response_payload' => json_encode($responsePayload),
            'created_at' => now(),
        ]);

        return response()->json($responsePayload, 200);
    }
}