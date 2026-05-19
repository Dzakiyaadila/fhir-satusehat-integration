<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'nama_ruangan',
        'location_id_satusehat',
        'org_id',
    ];
}