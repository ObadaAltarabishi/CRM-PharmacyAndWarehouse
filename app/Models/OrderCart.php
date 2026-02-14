<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCart extends Model
{
    protected $fillable = [
        'pharmacy_id',
        'warehouse_id',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(OrderCartItem::class);
    }
}
