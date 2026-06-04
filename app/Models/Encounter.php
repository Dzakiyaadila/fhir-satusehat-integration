<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encounter extends Model
{
    protected $fillable = [
        'nik_pasien',
        'ihs_pasien',
        'nama_pasien',
        'ihs_dokter',
        'nama_dokter',
        'location_id_satusehat',
        'encounter_id_satusehat',
        'nomor_internal',
        'waktu_kunjungan',
        'waktu_masuk_ruang',
        'status',
    ];
}