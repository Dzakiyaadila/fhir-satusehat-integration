<?php

namespace App\Http\Controllers;

use App\Services\LocationService;

class LocationController extends Controller
{
    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function setupLocation()
    {
        $result = $this->locationService->getOrCreateLocation();

        return response()->json($result);
    }
}