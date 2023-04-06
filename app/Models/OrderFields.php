<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFields extends Model
{
    protected $fillable = ['order_id', 'field_name', 'field_slug', 'field_value'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
