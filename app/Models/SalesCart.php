<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesCart extends Model
{
    protected $fillable = [
        'pharmacy_id',
        'pending_paid_total',
        'pending_feedback',
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
