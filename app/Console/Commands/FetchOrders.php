<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FetchOrders extends Command
{
    protected $signature = 'fetch:orders';

    protected $description = 'Fetch kaspi orders from external API';

    public function handle()
    {
        // Получите заказы с помощью внешнего API и сохраните их в базу данных
        // Пример:
        $orders = $this->fetchOrdersFromExternalAPI();
        // Log::error('str #20 ' . $orders);
        // foreach ($orders as $order) {
        //     $this->info('1');

        //     // // Создайте новый заказ в базе данных
        //     // $newOrder = new Order;
        //     // $newOrder->customer_name = $order->customer_name;
        //     // $newOrder->customer_email = $order->customer_email;
        //     // $newOrder->status_id = 1; // Установите статус заказа по умолчанию
        //     // $newOrder->save();

        //     // // Создайте поля заказа в базе данных
        //     // foreach ($order->fields as $field) {
        //     //     $newField = new OrderField;
        //     //     $newField->order_id = $newOrder->id;
        //     //     $newField->name = $field->name;
        //     //     $newField->value = $field->value;
        //     //     $newField->save();
        //     // }
        // }

        // $this->info('Orders have been fetched successfully.');
    }

    private function fetchOrdersFromExternalAPI()
    {
        $client = new Client();
        $response = $client->request('GET', 'https://kaspi.kz/shop/api/v2/orders', [
            'headers' => [
                'Content-Type' => 'application/vnd.api+json',
                'X-Auth-Token' => 'v5fgjD5Y2v7++RytwB2RV0ndMqBbVgSpAaE/EytLwgw='
            ],
            'query' => [
                'page[number]' => '0',
                'page[size]' => '1000',
                'filter[orders][state]' => 'ARCHIVE',
                'filter[orders][creationDate][$ge]' => '1679881434000',
                'filter[orders][creationDate][$le]' => '1680054234000',
            ]
        ]);

        $body = $response->getBody();
        $content = $body->getContents();

        Log::error('str #56 ' . $content);

        return $content;
    }
}
