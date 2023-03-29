<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status_name')->unique();
            $table->string('status_description');
            $table->timestamps();
        });

        DB::table('order_statuses')->insert(
            array(
                array('status_name' => 'New', 'status_description' => 'New Order'),
                array('status_name' => 'Processing', 'status_description' => 'Order in Processing'),
                array('status_name' => 'Completed', 'status_description' => 'Order Completed'),
                array('status_name' => 'Cancelled', 'status_description' => 'Order Cancelled')
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_statuses');
    }
}
