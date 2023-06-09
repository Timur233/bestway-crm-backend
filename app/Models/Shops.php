<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shops extends Model
{
    use HasFactory;

    protected $table = 'kaspi_shops';

    protected $fillable = [
        'kaspi_token',
        'kaspi_shop_id',
        'kaspi_shop_name',
        'title',
    ];
}
