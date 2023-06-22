<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\Shops;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderFields;
use App\Models\Customer;
use App\Models\CustomerFields;
use App\Models\CustomerAdreses;

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

    private function fetch_orders($status = 'NEW', $start_date, $end_date, $shop_token) {
        $params = http_build_query([
            'page[number]' => '0',
            'page[size]' => '1000',
            'filter[orders][state]' => $status,
            'filter[orders][creationDate][$ge]' => $start_date,
            'filter[orders][creationDate][$le]' => $end_date,
        ]);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://kaspi.kz/shop/api/v2/orders?" . $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/vnd.api+json",
                "X-Auth-Token: " . $shop_token
            ],
        ]);

        $resp = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error):
            Log::error('fetch orders error ' . $error);

            return [];
        endif;

        $kaspiOrders = json_decode($resp);
        $orders = [];

        if (isset($kaspiOrders->data) && !empty($kaspiOrders->data)) {
            foreach ($kaspiOrders->data as $kaspiOrder) {
                $order_data = [
                    'status_id' => '',
                    'order_number' => $kaspiOrder->id,
                    'order_description' => 'Kaspi order id=' . $kaspiOrder->id,
                    'order_total' => $kaspiOrder->attributes->totalPrice,
                    'delivery_type' => $kaspiOrder->attributes->deliveryMode,
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
                        'https://kaspi.kz/shop/api/v2/orders/' . $kaspiOrder->id . '/entries', $shop_token
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

                $orders[] = $order_data;
            }
        }

        $this->print_response($orders);

        return $orders;
    }

    private function fetch_order_entries($link, $shop_token) {
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
                "X-Auth-Token: " . $shop_token
            ],
        ]);

        $resp = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error):
            Log::error('fetch orders error ' . $error);

            return [];
        endif;

        $order_entries = json_decode($resp);
        $entries = [];

        if (isset($order_entries->data) && !empty($order_entries->data)) {
            foreach ($order_entries->data as $entry_data) {
                $entries[] = [
                    'id' => $entry_data->id,
                    'quantity' => $entry_data->attributes->quantity,
                    'price' => $entry_data->attributes->basePrice,
                    'delivery_cost' => $entry_data->attributes->deliveryCost,
                    'product' => $this->fetch_order_item(
                        'https://kaspi.kz/shop/api/v2/orderentries/' . $entry_data->id . '/product', $shop_token
                    ),
                ];
            }
        }

        return $entries;
    }

    private function fetch_order_item($link, $shop_token) {
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
                "X-Auth-Token: " . $shop_token
            ],
        ]);

        $resp = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error):
            Log::error('fetch orders error ' . $error);

            return [];
        endif;

        $order_product = json_decode($resp);
        $product = [];

        if (isset($order_product->data) && !empty($order_product->data)) {
            $product = [
                'id' => $order_product->data->id,
                'code' => $order_product->data->attributes->code,
                'name' => $order_product->data->attributes->name,
                'our_product' => $this->fetch_our_product(
                    'https://kaspi.kz/shop/api/v2/masterproducts/' . $order_product->data->id . '/merchantProduct', $shop_token
                )
            ];
        }

        return $product;
    }

    private function fetch_our_product($link, $shop_token) {
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
                "X-Auth-Token: " . $shop_token
            ],
        ]);

        $resp = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error):
            Log::error('fetch orders error ' . $error);

            return [];
        endif;

        $our_product = json_decode($resp);
        $product = [];

        if (isset($our_product->data) && !empty($our_product->data)) {
            $product = [
                'id' => $our_product->data->id,
                'code' => $our_product->data->attributes->code,
                'name' => $our_product->data->attributes->name,
            ];
        }

        return $product;
    }

    private function telegram_alert($mess, $buttons) {
        // $this->print_response($mess);
        $token='564889425:AAHlzDFCddWRJgT87OzjpC6hjyAihb9lgvU';
        $replyMarkup = array(
            'inline_keyboard' => $buttons
        );
        $query = [
            'chat_id' => -1001975478546,
            'parse_mode' => 'HTML',
            'text' => $mess,
            'reply_markup' => json_encode($replyMarkup)
        ];

        file_get_contents(
            sprintf(
                'https://api.telegram.org/bot%s/sendMessage?%s',
                $token,
                http_build_query($query),
            )
        );
    }

    /**
     * customerId <intenger>
     * adresData <object>
     *
     */
    private function create_customer_adres($customer_id, $adres) {
        if ($adres === []): return null; endif;

        if ($adres['town'] === '' || $adres['street_name'] === '' || $adres['street_number'] === ''):
            return null;
        endif;

        $find_adres = CustomerAdreses::where([
            ['customer_id', '=', $customer_id],
            ['town', '=', $adres['town']],
            ['street_name', '=', $adres['street_name']],
            ['street_number', '=', $adres['street_number']],
            ['apartment', '=', $adres['apartment']],
        ])->first();

        if ($find_adres) {
            return $find_adres->id;
        }

        $new_adres = new CustomerAdreses();

        $new_adres->customer_id = $customer_id;
        $new_adres->street_name = $adres['street_name'];
        $new_adres->street_number = $adres['street_number'];
        $new_adres->town = $adres['town'];
        $new_adres->district = $adres['district'];
        $new_adres->building = $adres['building'];
        $new_adres->apartment = $adres['apartment'];
        $new_adres->latitude = $adres['latitude'];
        $new_adres->longitude = $adres['longitude'];

        $new_adres->save();

        return $new_adres->id;
    }

    public function update_customer_fields($customer_id, $adres, $fields) {
        $customer_fields = [];

        foreach ($fields as $field) {
            $customer_fields[] = array_merge(['customer_id' => $customer_id], $field);
        }

        if ($adres) {
            $customer_fields[] = [
                'customer_id' => $customer_id,
                'name' => 'Актуальный адрес',
                'slug' => 'actual_adres',
                'value' => $adres
            ];
        }

        CustomerFields::where('customer_id', $customer_id)->delete();
        CustomerFields::insert($customer_fields);

        return true;
    }

    public function create_customer($customer, $delivery_data) {
        if ($customer['customer_phone'] === ''):
            return ['id' => null, 'adres_id' => null];
        endif;

        $find_customer = Customer::where('customer_phone', '=', $customer['customer_phone'])->first();

        if ($find_customer) {
            $customer_adres = $this->create_customer_adres(
                $find_customer->id,
                $customer['cutomer_adres']
            );

            $this->update_customer_fields(
                $find_customer->id,
                $customer_adres,
                $customer['customer_fields']
            );

            return ['id' => $find_customer->id, 'adres_id' => $customer_adres];
        }

        $new_customer = new Customer();
        $new_customer->customer_name = $customer['customer_name'];
        $new_customer->customer_phone = $customer['customer_phone'];
        $new_customer->save();

        $customer_adres = $this->create_customer_adres(
            $new_customer->id,
            $customer['cutomer_adres']
        );

        $this->update_customer_fields(
            $new_customer->id,
            $customer_adres,
            $customer['customer_fields']
        );

        return ['id' => $new_customer->id, 'adres_id' => $customer_adres];
    }

    private function update_order_fields($order_id, $adres_id, $fields) {
        $order_fields = [];

        foreach ($fields as $field) {
            $order_fields[] = array_merge(['order_id' => $order_id,], $field);
        }

        if ($adres_id !== null) {
            $order_fields[] = [
                'order_id' => $order_id,
                'field_name' => 'Адрес доставки',
                'field_slug' => 'order_delivery_adres_id',
                'field_value' => $adres_id
            ];
        }

        OrderFields::where('order_id', $order_id)->delete();
        OrderFields::insert($order_fields);

        return true;
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

        $find_order = Order::where('order_number', '=', $order_data['order_number'])->first();
        $customer = $this->create_customer(
            $order_data['customer'],
            $order_data['customer']['cutomer_adres']
        );

        if ($find_order) {
            $find_order->update(['status_id' => $status_id]);
            $find_order->update(['customer_id' => $customer['id']]);
            $find_order->update(['order_total' => $order_data['order_total']]);
            $find_order->update(['order_description' => $order_data['entries_in_line']]);

            $this->update_order_fields($find_order->id, $customer['adres_id'], $order_data['order_fields']);

            return $find_order->id;
        }

        $new_order = new Order();

        $new_order->order_number = $order_data['order_number'];
        $new_order->order_description = $order_data['entries_in_line'];
        $new_order->status_id = $status_id;
        $new_order->customer_id = $customer['id'];
        $new_order->order_total = $order_data['order_total'];

        $new_order->save();

        $this->update_order_fields($new_order->id, $customer['adres_id'], $order_data['order_fields']);


        ///////////////////////////////////
        $date = date("d M Y H:i:s");

        $code = OrderFields::where([['order_id', '=', $new_order->id], ['field_slug', '=', 'kaspi_code']])->first()['field_value'];
        $total = number_format($new_order->order_total, 0, ' ', ' ');

        if ($order_data['delivery_type'] != 'DELIVERY_PICKUP') {
            $customer_adres = <<< ADRES
            Адрес: {$order_data['customer']['cutomer_adres']['town']}, {$order_data['customer']['cutomer_adres']['street_name']}, {$order_data['customer']['cutomer_adres']['street_number']}
            ADRES;
        }

        $this->telegram_alert(<<< MESSAGE
        Дата: {$date}

        Магазин: {$shop_title}
        Номер заказа: {$code}
        Статус заказа: {$new_order->status->status_description}

        Cостав заказа:
        {$order_data['entries_in_line']}

        Клиент: {$new_order->customer->customer_name}
        Телфон: <a href='8{$new_order->customer->customer_phone}'>+7{$new_order->customer->customer_phone}</a>
        {$customer_adres}

        Cумма заказа: {$total} тг.

        Ссылка: https://kaspi.kz/merchantcabinet/#/orders/details/{$code}
        MESSAGE, [
            [
                [
                    'text' => 'Написать Whatsapp',
                    'url' => 'https://api.whatsapp.com/send?phone=7' . $new_order->customer->customer_phone
                ],
            ]
        ]);
        ///////////////////////////////////



        return $new_order->id;
    }

    public function index(Request $request) {
        $shop = Shops::where('id', '=', $request->input('id'))->first();
        $statuses = OrderStatus::all();
        $hasError = false;
        $resString = 'Загрузка заказов прошла успешно!';

        foreach ($statuses as $status) {
            $kaspiOrders = $this->fetch_orders(
                $status->status_name,
                strtotime("-1 hour") . '000', // -1 вфн
                strtotime('now') . '000',
                $shop->kaspi_token,
            );

            foreach ($kaspiOrders as $order) {
                try {
                    $order['entries_in_line'] = '';

                    foreach ($order['entries'] as $entry) {
                        $order['entries_in_line'] .= $entry['product']['our_product']['name'] . ' - ' . $entry['quantity'] . ' x ' . $entry['price'] . 'гт. \n';
                    }

                    $this->create_order($order, $status->id, $shop->title);
                } catch (\Throwable $th) {
                    $hasError = true;
                    $resString = '<pre>Возникла ошибка: \n'. $th . '</pre>';
                }
            }
        }

        return $resString;
    }

    public function all() {
        $start = strtotime('2019-06-01') * 1000;
        $end = time() * 1000;
        $resString = 'Загрузка заказов прошла успешно!';

        while ($start <= $end) {
            $next_end = $start + (14 * 24 * 60 * 60 * 1000);
            $statuses = OrderStatus::all();

            foreach ($statuses as $status) {
                $kaspiOrders = $this->fetch_orders(
                    $status->status_name,
                    $start,
                    $next_end
                );

                if (!$kaspiOrders) { break; }

                foreach ($kaspiOrders as $order) {
                    try {
                        $this->create_order($order, $status->id);
                    } catch (\Throwable $th) {
                        $hasError = true;
                        $resString = '<pre>Возникла ошибка: \n'. $th . '</pre>';
                    }
                }
            }

            $start = $next_end;
        }

        $statuses = OrderStatus::all();
        $hasError = false;
        $resString = 'Загрузка заказов прошла успешно!';

        return $resString;
    }
}
