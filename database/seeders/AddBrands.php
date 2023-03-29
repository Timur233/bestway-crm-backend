<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddBrands extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('product_brands')->insert([
            [
                'title' => 'Bestway',
                'alias' => 'bestway'
            ]
        ]);
    }
}
