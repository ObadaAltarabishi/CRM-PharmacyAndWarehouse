<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseInvoice extends Model
{
    protected $fillable = [
        'pharmacy_id',
        'warehouse_id',
        'amount',
        'created_by_name',
        'description',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
