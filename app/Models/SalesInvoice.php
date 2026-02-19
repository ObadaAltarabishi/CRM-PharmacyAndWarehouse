<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $fillable = [
        'pharmacy_id',
        'total_price',
        'paid_total',
        'discount_percent',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }
}
