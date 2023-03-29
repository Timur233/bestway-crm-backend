<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);

            $table->string('title');

            $table->string('sku');
            $table->string('sku_kaspi');
            $table->string('ean');

            $table->bigInteger('brand_id')->unsigned();

            $table->boolean('is_preorder');
            $table->integer('preorder_days');

            $table->bigInteger('price_id')->unsigned();
            $table->integer('min_price');

            $table->string('kaspi_link');
            $table->boolean('is_parse');

            $table->bigInteger('parent_id')->nullable()->unsigned();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
