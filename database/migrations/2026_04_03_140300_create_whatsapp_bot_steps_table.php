<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappBotStepsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_bot_steps', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('title');
            $table->text('reply_text');
            $table->json('trigger_keywords')->nullable();
            $table->json('options')->nullable();
            $table->string('fallback_step_slug', 64)->nullable();
            $table->boolean('is_entry')->default(false);
            $table->boolean('transfer_to_manager')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('whatsapp_bot_steps')->insert([
            [
                'slug' => 'welcome',
                'title' => 'Главное меню',
                'reply_text' => "Здравствуйте! Вас приветствует Bestway.\n\nЧем помочь?\n1. Каталог бассейнов\n2. Химия\n3. Доставка и оплата\n4. Цены и наличие\n5. Менеджер",
                'trigger_keywords' => json_encode(['start', 'menu', 'привет', 'здравствуйте', 'начать']),
                'options' => json_encode([
                    ['keywords' => ['1', 'каталог', 'бассейн', 'бассейны'], 'next_step_slug' => 'catalog'],
                    ['keywords' => ['2', 'химия'], 'next_step_slug' => 'chemistry'],
                    ['keywords' => ['3', 'доставка', 'оплата'], 'next_step_slug' => 'delivery'],
                    ['keywords' => ['4', 'цена', 'цены', 'наличие'], 'next_step_slug' => 'pricing'],
                    ['keywords' => ['5', 'менеджер', 'оператор', 'человек'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'welcome',
                'is_entry' => true,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'catalog',
                'title' => 'Каталог бассейнов',
                'reply_text' => "У нас есть:\n1. Каркасные бассейны\n2. Надувные бассейны\n3. Аксессуары\n4. Назад\n5. Менеджер",
                'trigger_keywords' => json_encode(['каталог', 'бассейн', 'бассейны']),
                'options' => json_encode([
                    ['keywords' => ['1', 'каркасные'], 'next_step_slug' => 'frame_pools'],
                    ['keywords' => ['2', 'надувные'], 'next_step_slug' => 'inflatable_pools'],
                    ['keywords' => ['3', 'аксессуары'], 'next_step_slug' => 'accessories'],
                    ['keywords' => ['4', 'назад'], 'next_step_slug' => 'welcome'],
                    ['keywords' => ['5', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'catalog',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'frame_pools',
                'title' => 'Каркасные бассейны',
                'reply_text' => "Каркасные бассейны подойдут для семьи и сезона.\nНапишите желаемый размер или бюджет, либо выберите:\n1. Назад\n2. Менеджер",
                'trigger_keywords' => json_encode(['каркасные']),
                'options' => json_encode([
                    ['keywords' => ['1', 'назад'], 'next_step_slug' => 'catalog'],
                    ['keywords' => ['2', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'frame_pools',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'inflatable_pools',
                'title' => 'Надувные бассейны',
                'reply_text' => "Надувные бассейны удобны для быстрого старта и детских зон.\nНапишите возраст детей или размер, либо выберите:\n1. Назад\n2. Менеджер",
                'trigger_keywords' => json_encode(['надувные']),
                'options' => json_encode([
                    ['keywords' => ['1', 'назад'], 'next_step_slug' => 'catalog'],
                    ['keywords' => ['2', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'inflatable_pools',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'accessories',
                'title' => 'Аксессуары',
                'reply_text' => "Из аксессуаров можем предложить насосы, лестницы, тенты, подстилки и фильтры.\n1. Назад\n2. Менеджер",
                'trigger_keywords' => json_encode(['аксессуары']),
                'options' => json_encode([
                    ['keywords' => ['1', 'назад'], 'next_step_slug' => 'catalog'],
                    ['keywords' => ['2', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'accessories',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'chemistry',
                'title' => 'Химия',
                'reply_text' => "По химии для бассейнов можем помочь с подбором:\n1. Для запуска бассейна\n2. Для регулярного ухода\n3. От мутной воды\n4. Назад\n5. Менеджер",
                'trigger_keywords' => json_encode(['химия']),
                'options' => json_encode([
                    ['keywords' => ['1', 'запуск'], 'next_step_slug' => 'chemistry_start'],
                    ['keywords' => ['2', 'уход'], 'next_step_slug' => 'chemistry_care'],
                    ['keywords' => ['3', 'мутная вода'], 'next_step_slug' => 'chemistry_water'],
                    ['keywords' => ['4', 'назад'], 'next_step_slug' => 'welcome'],
                    ['keywords' => ['5', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'chemistry',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'chemistry_start',
                'title' => 'Химия для запуска',
                'reply_text' => "Для запуска бассейна обычно подбираем стартовый набор химии под объем воды.\nНапишите объем бассейна или модель, либо выберите:\n1. Назад\n2. Менеджер",
                'trigger_keywords' => json_encode(['запуск']),
                'options' => json_encode([
                    ['keywords' => ['1', 'назад'], 'next_step_slug' => 'chemistry'],
                    ['keywords' => ['2', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'chemistry_start',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 70,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'chemistry_care',
                'title' => 'Химия для ухода',
                'reply_text' => "Для регулярного ухода подскажем хлор, альгицид, коагулянт и тестеры.\n1. Назад\n2. Менеджер",
                'trigger_keywords' => json_encode(['уход']),
                'options' => json_encode([
                    ['keywords' => ['1', 'назад'], 'next_step_slug' => 'chemistry'],
                    ['keywords' => ['2', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'chemistry_care',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'chemistry_water',
                'title' => 'Проблемы с водой',
                'reply_text' => "Если вода мутная или зеленеет, лучше подобрать средство под объем бассейна и тип фильтра.\n1. Назад\n2. Менеджер",
                'trigger_keywords' => json_encode(['мутная вода']),
                'options' => json_encode([
                    ['keywords' => ['1', 'назад'], 'next_step_slug' => 'chemistry'],
                    ['keywords' => ['2', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'chemistry_water',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'delivery',
                'title' => 'Доставка и оплата',
                'reply_text' => "Подскажем по доставке и оплате. Для точного расчета лучше написать город.\n1. Назад\n2. Менеджер",
                'trigger_keywords' => json_encode(['доставка', 'оплата']),
                'options' => json_encode([
                    ['keywords' => ['1', 'назад'], 'next_step_slug' => 'welcome'],
                    ['keywords' => ['2', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'delivery',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'pricing',
                'title' => 'Цены и наличие',
                'reply_text' => "Для проверки цены и наличия напишите модель, размер бассейна или артикул.\n1. Назад\n2. Менеджер",
                'trigger_keywords' => json_encode(['цена', 'цены', 'наличие']),
                'options' => json_encode([
                    ['keywords' => ['1', 'назад'], 'next_step_slug' => 'welcome'],
                    ['keywords' => ['2', 'менеджер', 'оператор'], 'next_step_slug' => 'manager'],
                ]),
                'fallback_step_slug' => 'pricing',
                'is_entry' => false,
                'transfer_to_manager' => false,
                'is_active' => true,
                'sort_order' => 110,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'manager',
                'title' => 'Перевод на менеджера',
                'reply_text' => "Перевожу вас на менеджера. Напишите вопрос подробнее, и мы ответим вручную как можно быстрее.",
                'trigger_keywords' => json_encode(['менеджер', 'оператор', 'человек']),
                'options' => null,
                'fallback_step_slug' => null,
                'is_entry' => false,
                'transfer_to_manager' => true,
                'is_active' => true,
                'sort_order' => 120,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_bot_steps');
    }
}
