<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['status_id', 'order_number', 'order_description'];

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    public function fields()
    {
        return $this->hasMany(OrderField::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity');
    }
}
