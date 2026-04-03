<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappContactsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32)->unique();
            $table->string('chat_id', 64)->unique();
            $table->string('name')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_contacts');
    }
}
