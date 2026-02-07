<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
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


}
