<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pharmacy extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'pharmacy_name',
        'doctor_name',
        'doctor_phone',
        'doctor_email',
        'password',
        'activated_at',
        'region_id',
        'admin_id',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
