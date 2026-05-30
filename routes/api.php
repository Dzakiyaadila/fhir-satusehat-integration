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
// Route::post('/location', [SatuSehatController::class, 'postLocation'])->name('location.store');
// Route::post('/encounter', [SatuSehatController::class, 'postEncounter'])->name('encounter.store');