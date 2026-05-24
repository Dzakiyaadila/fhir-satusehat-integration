<?php

namespace App\Services; 

use Illuminate\Support\Facades\Http;

class MasterDataService
{
    protected $baseUrl;
    protected $authService;

    // Inject AuthService ke dalam MasterDataService
    public function __construct(AuthService $authService)
    {
        $this->baseUrl = env('SATUSEHAT_BASE_URL');
        $this->authService = $authService;
    }

    
}