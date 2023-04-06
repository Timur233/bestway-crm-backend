<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerAdresesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_adreses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('street_name');
            $table->string('street_number');
            $table->string('town');
            $table->string('district')->nullable();
            $table->string('building')->nullable();
            $table->string('apartment')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_adreses');
    }
}
