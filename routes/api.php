<?php

use App\Http\Controllers\SatuSehatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;

Route::get('/test-setup', [SatusehatController::class, 'testSetup']);

Route::get('/satusehat/daftar', [SatuSehatController::class, 'daftarkanPasien']);
// Placeholder untuk stream lain (uncomment nanti):

// Route::post('/auth/token', [SatusehatController::class, 'getToken'])->name('auth.token');
// Route::get('/patient/{nik}', [SatusehatController::class, 'getPatientIhs'])->name('patient.ihs');
// Route::get('/practitioner/{nik}', [SatusehatController::class, 'getPractitionerIhs'])->name('practitioner.ihs');
Route::get('/location', [LocationController::class, 'setupLocation'])->name('location.store');
// Route::post('/encounter', [SatusehatController::class, 'postEncounter'])->name('encounter.store');

