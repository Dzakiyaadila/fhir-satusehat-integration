<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Models\Location;
use Illuminate\Support\Facades\Http;

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

    // Stream 2: Auth
    // Stream 3: IHS Number
    // Stream 4: Location
    private function getAccessToken()
    {
        $response = Http::asForm()->post(
            env('SATUSEHAT_AUTH_URL') . '/accesstoken?grant_type=client_credentials',
            [
                'client_id' => env('SATUSEHAT_CLIENT_ID'),
                'client_secret' => env('SATUSEHAT_CLIENT_SECRET'),
            ]
        );

        if ($response->failed()) {
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }

    public function postLocation(): JsonResponse
{
    // cek apakah location sudah ada
    $existingLocation = Location::first();

    if ($existingLocation) {
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Location already registered',
            'data' => $existingLocation
        ], 200);
    }

    // ambil access token
    $token = $this->getAccessToken();

    if (!$token) {
        return response()->json([
            'status' => 'ERROR',
            'message' => 'Failed to get access token'
        ], 500);
    }

    // payload FHIR R4
    $payload = [
        "resourceType" => "Location",
        "status" => "active",
        "name" => "Ruang Poli Umum",
        "managingOrganization" => [
            "reference" => "Organization/" . env('SATUSEHAT_ORG_ID')
        ]
    ];

    // request ke SATUSEHAT
    $response = Http::withToken($token)
        ->withHeaders([
            'Content-Type' => 'application/json'
        ])
        ->post(
            env('SATUSEHAT_BASE_URL') . '/Location',
            $payload
        );

    // kalau gagal
    if ($response->failed()) {
        return response()->json([
            'status' => 'ERROR',
            'message' => 'Failed create location',
            'response' => $response->json()
        ], $response->status());
    }

    // ambil id dari response SATUSEHAT
    $locationId = $response->json()['id'] ?? null;

    // simpan ke database
    $location = Location::create([
        'nama_ruangan' => 'Ruang Poli Umum',
        'location_id_satusehat' => $locationId,
        'org_id' => env('SATUSEHAT_ORG_ID')
    ]);

    return response()->json([
        'status' => 'SUCCESS',
        'message' => 'Location created successfully',
        'data' => $location
    ], 201);
}
    // Stream 5: Encounter
}