<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kaspi_shops', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('kaspi_shop_id');
            $table->string('kaspi_shop_name');
            $table->timestamps();
        });

        Schema::create('kaspi_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('kaspi_stock_id');
            $table->bigInteger('shop_id')->unsigned();
            $table->foreign('shop_id')->references('id')->on('kaspi_shops');
            $table->boolean('is_main')->default(false);
            $table->timestamps();
        });

        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->bigInteger('stock_id')->unsigned();
            $table->foreign('stock_id')->references('id')->on('kaspi_stocks');
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
        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('kaspi_stocks');
        Schema::dropIfExists('kaspi_shops');
    }
}
