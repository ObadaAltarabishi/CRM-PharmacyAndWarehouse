<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesCart extends Model
{
    protected $fillable = [
        'pharmacy_id',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function items()
    {
        return $this->hasMany(SalesCartItem::class);
    }
}
