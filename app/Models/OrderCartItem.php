<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCartItem extends Model
{
    protected $fillable = [
        'order_cart_id',
        'product_id',
        'quantity',
    ];

    public function cart()
    {
        return $this->belongsTo(OrderCart::class, 'order_cart_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
