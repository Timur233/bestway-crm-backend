<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CustomerFields;
use App\Models\CustomerAdreses;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_phone',
    ];

    public function fields()
    {
        return $this->hasMany(CustomerFields::class);
    }

    public function adreses()
    {
        return $this->hasMany(CustomerAdreses::class);
    }
}
