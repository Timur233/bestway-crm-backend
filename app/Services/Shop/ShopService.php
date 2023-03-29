<?php

namespace App\Services\Shop;

class ShopService
{

    public function getItems()
    {
        return Shop::all();
    }

}

?>
