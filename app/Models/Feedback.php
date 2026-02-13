<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'content',
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
}
