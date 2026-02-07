<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function admins()
    {
        return $this->hasMany(Admin::class);
    }

    public function pharmacies()
    {
        return $this->hasMany(Pharmacy::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}

