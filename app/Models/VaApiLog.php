<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaApiLog extends Model
{
    use HasFactory;

    protected $table = 'va_api_logs';

    protected $fillable = [
        'endpoint',
        'success',
        'status_code',
        'rcode',
        'message',
        'request_data',
        'response_data',
        'duration_ms',
    ];

    protected $casts = [
        'success' => 'boolean',
        'request_data' => 'array',
        'response_data' => 'array',
    ];
}
