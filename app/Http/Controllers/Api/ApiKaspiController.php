<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ApiTestController;
use App\Models\Order;
use App\Models\OrderFields;
use App\Models\OrderStatus;
use App\Models\Shops;
use App\Services\Notifications\TelegramAlertService;
use App\Services\Order\OrderPersistenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ApiKaspiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private function print_response($resp) {

        if (!env('APP_DEBUG')) { return; }

        echo "<pre>";
        print_r($resp);
        echo "</pre>";
    }

    private function fetch_orders($start_date, $end_date, $shop_token, $shop_id, $status = 'NEW') {
        date_default_timezone_set('Asia/Almaty');

        $params = http_build_query([
            'page[number]' => '0',
            'page[size]' => '1000',
            'filter[orders][state]' => $status,
            'filter[orders][creationDate][$ge]' => $start_date,
            'filter[orders][creationDate][$le]' => $end_date,
        ]);

        $kaspiOrders = $this->get_kaspi_response(
            'https://kaspi.kz/shop/api/v2/orders?' . $params,
            $shop_token,
            $shop_id
        );
        $orders = [];

        if (isset($kaspiOrders->data) && !empty($kaspiOrders->data)) {
            foreach ($kaspiOrders->data as $kaspiOrder) {
                $orders[] = $this->map_kaspi_order($kaspiOrder, $shop_token, $shop_id);
            }
        }

        return $orders;
    }

    private function fetch_order_entries($link, $shop_token, $shop_id) {
        $order_entries = $this->get_kaspi_response($link, $shop_token, $shop_id);
        $entries = [];

        if (isset($order_entries->data) && !empty($order_entries->data)) {
            foreach ($order_entries->data as $entry_data) {
                $entries[] = [
                    'id' => $entry_data->id,
                    'quantity' => $entry_data->attributes->quantity,
                    'price' => $entry_data->attributes->basePrice,
                    'delivery_cost' => $entry_data->attributes->deliveryCost,
                    'product' => $this->fetch_order_item(
                        'https://kaspi.kz/shop/api/v2/orderentries/' . $entry_data->id . '/product', $shop_token, $shop_id
                    ),
                ];
            }
        }

        return $entries;
    }

    private function fetch_order_item($link, $shop_token, $shop_id) {
        $order_product = $this->get_kaspi_response($link, $shop_token, $shop_id);
        $product = [];

        if (isset($order_product->data) && !empty($order_product->data)) {
            $product = [
                'id' => $order_product->data->id,
                'code' => $order_product->data->attributes->code,
                'name' => $order_product->data->attributes->name,
                'our_product' => $this->fetch_our_product(
                    'https://kaspi.kz/shop/api/v2/masterproducts/' . $order_product->data->id . '/merchantProduct', $shop_token, $shop_id
                )
            ];
        }

        return $product;
    }

    private function fetch_our_product($link, $shop_token, $shop_id) {
        $our_product = $this->get_kaspi_response($link, $shop_token, $shop_id);
        $product = [];

        if (isset($our_product->data) && !empty($our_product->data)) {
            $product = [
                'id' => $our_product->data->id,
                'code' => $our_product->data->attributes->code,
                'name' => $our_product->data->attributes->name,
            ];

            echo "////";
            // print_r($product);
            echo "////";
        }

        return $product;
    }

    private function get_kaspi_response($link, $shop_token, $shop_id) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $link,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/vnd.api+json",
                "X-Auth-Token: " . $shop_token,
                "X-Merchant-Uid: " . $shop_id,
            ],
        ]);

        $resp = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error):
            Log::error('fetch orders error ' . $error);

            return [];
        endif;

        return json_decode($resp);
    }

    private function telegram_alert($mess, $buttons) {
        app(TelegramAlertService::class)->sendMessage($mess, $buttons);
    }

    private function telegram_photo(string $photoUrl, ?string $caption = null): void
    {
        app(TelegramAlertService::class)->sendPhoto($photoUrl, $caption);
    }

    public function create_order($order_data, $status_id, $shop_title) {
        /**
         * Создать или обновить заказ (таблица orders) +++
         * Создать поля (таблица order_fields) +++
         * Создать все поля заново (таблица order_fields) +++
         * Создать или обновить пользователя (таблица customers) +++
         * Удалить все поля пользователя (таблица customer_fields) +++
         * Создать все поля позьзователя из заказа (таблица customer_fields) +++
         * Cоздать адрес покупателя +++
         */

        $result = app(OrderPersistenceService::class)->persist($order_data, $status_id);
        $order = $result['order'];

        if (!$result['created']) {
            Log::info('Telegram alert skipped because order already exists.', [
                'order_id' => $order->id,
                'order_number' => $order_data['order_number'] ?? null,
            ]);
            $this->print_response('Редактирую заказ ХХХ ' . $order_data['order_number']);
            return $order->id;
        }

        $this->print_response('Создаю заказ ХХХ ' . $order_data['order_number']);

        ///////////////////////////////////
        $date = date("d M Y H:i:s");

        $code = OrderFields::where([['order_id', '=', $order->id], ['field_slug', '=', 'kaspi_code']])->first()['field_value'];
        $total = number_format($order->order_total, 0, ' ', ' ');
        $contactUrl = 'https://pay.kaspi.kz/chat?threadId=' . urlencode((string) $code) . '&type=CLIENT_SELLER_BY_ORDER&from=orderInfo_pay_web';
        $qrUrl = URL::temporarySignedRoute('order-list.qr', now()->addDays(7), [
            'code' => $code,
        ]);
        $customer_adres = '';

        if ($order_data['delivery_type'] != 'DELIVERY_PICKUP') {
            $customer_adres = <<< ADRES
            Адрес: {$order_data['customer']['cutomer_adres']['town']}, {$order_data['customer']['cutomer_adres']['street_name']}, {$order_data['customer']['cutomer_adres']['street_number']}
            ADRES;
        }
		
		$express_alert = "";

        if ($order_data['is_express']) {
			$express_alert = <<< EXPRESS_ALERT
            <b>Внимание:</b> Express Доставка 🚨🚨🚨
            <b>Дата доставки:</b> {$order_data['express_date']}
            EXPRESS_ALERT;
        }

        $this->telegram_alert(<<< MESSAGE
        Дата: {$date}

        Магазин: {$shop_title}
        Номер заказа: {$code}
        Статус заказа: {$order->status->status_description}

        {$express_alert}

        Cостав заказа:
        {$order_data['entries_in_line']}

        Клиент: {$order->customer->customer_name}
        Телфон: <a href='8{$order->customer->customer_phone}'>+7{$order->customer->customer_phone}</a>
        {$customer_adres}

        Cумма заказа: {$total} тг.

        Ссылка: https://kaspi.kz/merchantcabinet/#/orders/details/{$code}
        MESSAGE, [
            [
                [
                    'text' => 'Связаться с клиентом',
                    'url' => $contactUrl
                ],
            ]
        ]);

        $this->telegram_photo($qrUrl, 'QR для связи с клиентом по заказу ' . $code);

        foreach ($order_data['entries_list'] as $item) {
            ApiTestController::changeRemind($item['code'], $item['quantity']);
        }

        ///////////////////////////////////



        return $order->id;
    }

    public function index(Request $request) {
        $shop = Shops::where('id', '=', $request->input('id'))->first();
        $statuses = OrderStatus::all();
        $hasError = false;
        $resString = 'Загрузка заказов прошла успешно!';

        foreach ($statuses as $status) {
            $kaspiOrders = $this->fetch_orders(
                strtotime("-1 hour") . '000', // -1 вфн
                strtotime('now') . '000',
                $shop->kaspi_token,
                $shop->kaspi_shop_id,
                $status->status_name,
            );

            foreach ($kaspiOrders as $order) {
                try {
                    $order = $this->append_order_entries_meta($order);
                    $this->create_order($order, $status->id, $shop->title);
                } catch (\Throwable $th) {
                    $hasError = true;
                    $resString = '<pre>Возникла ошибка: \n'. $th . '</pre>';
                }
            }
        }

        return $resString;
    }

    public function all(Request $request) {
        $start = strtotime('2024-06-17') * 1000;
        $end = strtotime('2024-06-21') * 1000;
        $shop = Shops::where('id', '=', $request->input('id'))->first();
        $resString = 'Загрузка заказов прошла успешно!';

        while ($start <= $end) {
            $next_end = $start + (14 * 24 * 60 * 60 * 1000);
            $statuses = OrderStatus::all();

            $kaspiOrders = $this->fetch_orders(
                $start,
                $next_end,
                $shop->kaspi_token,
                $shop->kaspi_shop_id,
                'ARCHIVE',
            );

            foreach ($kaspiOrders as $order) {
                try {
                    $order = $this->append_order_entries_meta($order, false);
                    $this->create_order($order, $status->id, $shop->title);
                } catch (\Throwable $th) {
                    $hasError = true;
                    $resString = '<pre>Возникла ошибка: \n'. $th . '</pre>';
                }
            }

            $start = $next_end;
        }

        $statuses = OrderStatus::all();
        $hasError = false;
        $resString = 'Загрузка заказов прошла успешно!';

        return $resString;
    }

    private function map_kaspi_order($kaspiOrder, $shop_token, $shop_id) {
        $order_data = [
            'status_id' => '',
            'order_number' => $kaspiOrder->id,
            'order_description' => 'Kaspi order id=' . $kaspiOrder->id,
            'order_total' => $kaspiOrder->attributes->totalPrice,
            'delivery_type' => $kaspiOrder->attributes->deliveryMode,
            'is_express' => $kaspiOrder->attributes->isKaspiDelivery && $kaspiOrder->attributes->kaspiDelivery->express ? true : false,
            'express_date' => $kaspiOrder->attributes->isKaspiDelivery && $kaspiOrder->attributes->kaspiDelivery->express
                ? date('Y-m-d H:i:s', $kaspiOrder->attributes->kaspiDelivery->courierTransmissionPlanningDate / 1000) : '',
            'order_fields' => [
                [
                    'field_name' => 'Код на каспи',
                    'field_slug' => 'kaspi_code',
                    'field_value' => $kaspiOrder->attributes->code
                ],
                [
                    'field_name' => 'Дата поступления',
                    'field_slug' => 'order_date',
                    'field_value' => $kaspiOrder->attributes->creationDate
                ],
                [
                    'field_name' => 'Каспи доставка',
                    'field_slug' => 'kaspi_delivery',
                    'field_value' => $kaspiOrder->attributes->isKaspiDelivery
                ],
                [
                    'field_name' => 'Тип доставки',
                    'field_slug' => 'order_delivery_type',
                    'field_value' => $kaspiOrder->attributes->deliveryMode
                ],
            ],
            'customer' => [
                'customer_name' => $kaspiOrder->attributes->customer->firstName . ' ' . $kaspiOrder->attributes->customer->lastName,
                'customer_phone' => $kaspiOrder->attributes->customer->cellPhone,
                'cutomer_adres' => [],
                'customer_fields' => [
                    [
                        'name' => 'Имя',
                        'slug' => 'first_name',
                        'value' => $kaspiOrder->attributes->customer->firstName
                    ],
                    [
                        'name' => 'Фамилия',
                        'slug' => 'last_name',
                        'value' => $kaspiOrder->attributes->customer->lastName
                    ],
                    [
                        'name' => 'ID пользователя на Каспи',
                        'slug' => 'kaspi_user_id',
                        'value' => $kaspiOrder->attributes->customer->id
                    ],
                ],
            ],
            'entries' => $this->fetch_order_entries(
                'https://kaspi.kz/shop/api/v2/orders/' . $kaspiOrder->id . '/entries',
                $shop_token,
                $shop_id
            ),
        ];

        if ($kaspiOrder->attributes->deliveryMode != 'DELIVERY_PICKUP') {
            $order_data['customer']['cutomer_adres'] = [
                'street_name' => $kaspiOrder->attributes->deliveryAddress->streetName,
                'street_number' => $kaspiOrder->attributes->deliveryAddress->streetNumber,
                'town' => $kaspiOrder->attributes->deliveryAddress->town,
                'district' => $kaspiOrder->attributes->deliveryAddress->district,
                'building' => $kaspiOrder->attributes->deliveryAddress->building,
                'apartment' => $kaspiOrder->attributes->deliveryAddress->apartment,
                'latitude' => $kaspiOrder->attributes->deliveryAddress->latitude,
                'longitude' => $kaspiOrder->attributes->deliveryAddress->longitude,
            ];
        }

        return $order_data;
    }

    private function append_order_entries_meta($order, $includeEntriesList = true) {
        $order['entries_in_line'] = '';
        $order['entries_list'] = [];

        foreach ($order['entries'] as $entry) {
            $order['entries_in_line'] .= $entry['product']['our_product']['name'] . ' - ' . $entry['quantity'] . ' x ' . $entry['price'] . 'гт. \n';

            if ($includeEntriesList) {
                $order['entries_list'][] = [
                    'code' => $entry['product']['our_product']['code'],
                    'quantity' => $entry['quantity'],
                ];
            }
        }

        return $order;
    }
}
