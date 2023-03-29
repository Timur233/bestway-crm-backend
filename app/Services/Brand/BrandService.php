<?php

namespace App\Services\Brand;

use App\Models\Brands;

class BrandService
{

    public function getItems()
    {
        return Brands::all();
    }

}

?>
