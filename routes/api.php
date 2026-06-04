<?php

use App\Http\Controllers\SatusehatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-setup', [SatusehatController::class, 'testSetup']);

// Placeholder untuk stream lain (uncomment nanti):

Route::post('/auth/token', [SatusehatController::class, 'getToken'])->name('auth.token');
Route::get('/patient/{nik}', [SatusehatController::class, 'getPatientIhs'])->name('patient.ihs');
Route::get('/practitioner/{nik}', [SatusehatController::class, 'getPractitionerIhs'])->name('practitioner.ihs');
Route::post('/location', [SatusehatController::class, 'postLocation'])->name('location.store');
Route::post('/encounter', [SatusehatController::class, 'postEncounter'])->name('encounter.store');