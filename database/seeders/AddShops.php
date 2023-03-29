<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddShops extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('kaspi_shops')->insert([
            [
                'title' => 'Intexmania',
                'kaspi_shop_id' => 'BestWay',
                'kaspi_shop_name' => 'Intexmania-kz',
            ]
        ]);
    }
}
