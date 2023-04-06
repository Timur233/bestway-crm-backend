<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderStatus;
use App\Models\OrderFields;
use App\Models\Customer;

class Order extends Model
{
    protected $fillable = ['status_id', 'customer_id', 'order_number', 'order_description', 'order_total'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    public function fields()
    {
        return $this->hasMany(OrderFields::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity');
    }
}
