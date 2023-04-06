<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('name');
            $table->string('slug');
            $table->text('value');
            $table->timestamps();

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_fields');
    }
}
