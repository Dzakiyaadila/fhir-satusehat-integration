<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;

class SatuSehatController extends Controller
{
    protected $authService;
    protected $masterService;

    public function __construct(AuthService $authService, MasterDataService $masterService)

       {
        $this->authService = $authService;
        $this->masterService = $masterService;
    }

    public function testSetup(): JsonResponse
    {
        return response()->json([
            'status'      => 'OK',
            'message'     => 'SatuSehat Integration API is running',
            'environment' => config('app.env'),
        ], 200);
    }

    // Akan diisi stream 3, 4, 5
    // Stream 3: IHS Number
    // Stream 4: Location
    // Stream 5: Encounter
}