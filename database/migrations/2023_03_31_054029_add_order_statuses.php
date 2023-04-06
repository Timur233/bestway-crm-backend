<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderStatuses extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        // Удалить все записи из таблицы `orders`
        \DB::table('orders')->delete();

        // Очистить таблицу `order_statuses`
        \DB::table('order_statuses')->truncate();

        Schema::enableForeignKeyConstraints();

        // Добавить новые записи в таблицу `order_statuses`
        \DB::table('order_statuses')->insert([
            ['status_name' => 'NEW', 'status_description' => 'Новый'],
            ['status_name' => 'SIGN_REQUIRED', 'status_description' => 'На утверждении'],
            ['status_name' => 'PICKUP', 'status_description' => 'Самовывоз'],
            ['status_name' => 'DELIVERY', 'status_description' => 'Доставка'],
            ['status_name' => 'KASPI_DELIVERY', 'status_description' => 'Каспи доставка'],
            ['status_name' => 'ARCHIVE', 'status_description' => 'Завершен'],
        ]);
    }

    public function down()
    {
        // Откатить миграцию не имеет смысла, так как данные были удалены
    }
}
