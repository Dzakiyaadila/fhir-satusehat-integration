<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    protected $table = 'integration_logs';

    protected $fillable = [
        'step',
        'http_status',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
    ];
}