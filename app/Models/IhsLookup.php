<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IhsLookup extends Model
{
    protected $fillable = ['nik', 'tipe', 'ihs_number', 'ditemukan'];
    
    protected $casts = [
        'ditemukan' => 'boolean',
    ];
}