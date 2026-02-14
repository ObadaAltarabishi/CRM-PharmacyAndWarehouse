<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesCartItem extends Model
{
    protected $fillable = [
        'sales_cart_id',
        'product_id',
        'quantity',
    ];

    public function cart()
    {
        return $this->belongsTo(SalesCart::class, 'sales_cart_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
