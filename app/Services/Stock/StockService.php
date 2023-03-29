<?php

namespace App\Services\Stock;

class StockService
{

    public function getItems()
    {
        return Stock::all();
    }

}

?>
