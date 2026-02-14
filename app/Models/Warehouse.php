<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Warehouse extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'warehouse';

    protected $fillable = [
        'warehouse_name',
        'owner_name',
        'owner_phone',
        'owner_email',
        'password',
        'activated_at',
        'region_id',
        'admin_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
    
    public function region() { return $this->belongsTo(Region::class); }

    public function products()
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

}
