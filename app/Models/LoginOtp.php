<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginOtp extends Model
{
    protected $fillable = [
        'authenticatable_type',
        'authenticatable_id',
        'email',
        'request_token',
        'code_hash',
        'attempts',
        'resend_count',
        'last_sent_at',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
