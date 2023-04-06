<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRowsToKaspiShopsTable extends Migration
{
    public function up()
    {
        DB::table('kaspi_shops')->insert([[
            'kaspi_token' => 'v5fgjD5Y2v7++RytwB2RV0ndMqBbVgSpAaE/EytLwgw=',
            'title' => 'Intexmania',
            'kaspi_shop_id' => 'BestWay',
            'kaspi_shop_name' => 'Intexmania-kz',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'kaspi_token' => 'MRnVR/KodACrd8jMxw0LE3O263drl/W1jgmL5vHfrQE=',
            'title' => 'Power Steel',
            'kaspi_shop_id' => '10746011',
            'kaspi_shop_name' => 'Power Steel',
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        $intexmania_id = DB::table('kaspi_shops')->where('kaspi_shop_id', 'BestWay')->value('id');
        $powersteel_id = DB::table('kaspi_shops')->where('kaspi_shop_id', '10746011')->value('id');

        DB::table('kaspi_stocks')->insert([
            'title' => 'Магазин на Жандосова',
            'kaspi_stock_id' => 'PP1',
            'shop_id' => $intexmania_id,
            'is_main' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('kaspi_stocks')->insert([
            'title' => 'Магазин на Жандосова',
            'kaspi_stock_id' => '10746011',
            'shop_id' => $powersteel_id,
            'is_main' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
    }
}
