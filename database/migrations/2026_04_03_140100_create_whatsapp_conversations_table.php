<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('whatsapp_contacts')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('bot');
            $table->string('current_step_slug', 64)->nullable();
            $table->unsignedSmallInteger('unresolved_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('manager_requested_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
}
