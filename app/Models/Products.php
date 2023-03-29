<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    public function brands() {
        return $this->belongsTo(Brands::class);
    }

    public function categories() {
        return $this->belongsTo(Categories::class);
    }

    public function prices() {
        return $this->belongsTo(Prices::class);
    }
}
