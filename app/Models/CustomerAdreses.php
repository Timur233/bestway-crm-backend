<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAdreses extends Model
{
    use HasFactory;

    protected $fillable = [
        'street_name',
        'street_number',
        'town',
        'district',
        'building',
        'apartment',
        'latitude',
        'longitude'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
