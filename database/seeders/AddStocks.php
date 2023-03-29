<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddStocks extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('kaspi_stocks')->insert([
            [
                'title' => 'Магазин на Жандосова',
                'kaspi_stock_id' => 'PP1',
                'shop_id' => 1,
                'is_main' => true,
            ]
        ]);
    }
}
