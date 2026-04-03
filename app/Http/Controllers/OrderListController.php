<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderListController extends Controller
{
    public function index()
    {
		$orders = $products = DB::select(<<<SQL
			SELECT
				orders.id as id,
				DATE_ADD(orders.created_at, INTERVAL 5 HOUR) as order_date,
				orders.order_description as description,
				orders.order_total as total,
				kaspi_codes.field_value AS kaspi_code,
				statuses.status_description AS status,
				customers.customer_name as customer_name,
				customers.customer_phone as customer_phone,
				CONCAT('г. ', adres.town, ' ', adres.street_name, ' дом ', adres.street_number) as customer_adres
			FROM `orders` AS orders
			LEFT JOIN `order_statuses` AS statuses ON orders.status_id = statuses.id
			LEFT JOIN `customers` AS customers ON orders.customer_id = customers.id
			LEFT JOIN `customer_adreses` AS adres ON customers.id = adres.customer_id
			LEFT JOIN `order_fields` AS kaspi_codes 
			ON kaspi_codes.order_id = orders.id AND kaspi_codes.field_slug = 'kaspi_code'
			WHERE orders.created_at > '2025-06-20 00:00:00'
			AND orders.status_id != '6'
			ORDER BY `order_date` ASC;
		SQL);
        $data = [
            'page_title' => 'Список заказов от 20 июня',
            'page_description' => 'This is a description passed from the controller.',
			'orders' => (array) $orders
        ];

        // Возвращение представления с параметрами
        return view('orderlist', $data);
    }
}
