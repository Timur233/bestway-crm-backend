<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFields extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'value',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
