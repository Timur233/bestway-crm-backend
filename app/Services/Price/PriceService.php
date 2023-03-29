<?php

namespace App\Services\Price;

class PriceService
{

    public function getItems()
    {
        return Price::all();
    }

}

?>
