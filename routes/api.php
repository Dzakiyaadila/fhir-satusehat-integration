<?php

use App\Http\Controllers\SatuSehatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-setup', [SatuSehatController::class, 'testSetup']);

// Route::get('/satusehat/daftar', [SatuSehatController::class, 'daftarkanPasien']);
// Placeholder untuk stream lain (uncomment nanti):

// Route::post('/auth/token', [SatuSehatController::class, 'getToken'])->name('auth.token');
// Route::get('/patient/{nik}', [SatuSehatController::class, 'getPatientIhs'])->name('patient.ihs');
// Route::get('/practitioner/{nik}', [SatuSehatController::class, 'getPractitionerIhs'])->name('practitioner.ihs');
Route::post('/location/setup', [SatuSehatController::class, 'setupLocation'])->name('location.setup');
// Route::post('/encounter', [SatuSehatController::class, 'postEncounter'])->name('encounter.store');
Route::post('/register', [SatuSehatController::class, 'register'])->name('encounter.store');
Route::put('/encounter/{id}/in-progress', [SatuSehatController::class, 'updateEncounterInProgress'])->name('encounter.in-progress');
// Route::put('/encounter/{id}/finish', [SatuSehatController::class, 'updateEncounterFinish'])->name('encounter.finish');
Route::get('/integration/logs', [SatuSehatController::class, 'showIntegrationLogs'])->name('integration.logs');