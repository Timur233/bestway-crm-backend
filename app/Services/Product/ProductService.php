<?php

namespace App\Services\Product;

class ProductService
{

    public function getItems()
    {
        return Product::all();
    }

}

?>
