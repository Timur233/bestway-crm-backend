<?php

namespace App\Http\Controllers\Api;

use App\Services\Product\ProductRemainsService;
use Illuminate\Http\Request;

class ApiTestController extends ApiController
{

    /**
     * BrandController constructor
    */
    public function __construct()
    {
    }

    public static function changeRemind($sku, $quantity_in_order) {
        return app(ProductRemainsService::class)->changeRemind($sku, (int) $quantity_in_order);
    }

    public function test(Request $request) {
        self::changeRemind($request->input('sku'), 1);
    }

}
