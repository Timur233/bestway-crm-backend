<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::resource('products',   'Api\ApiProductController');
Route::resource('brands',     'Api\ApiBrandController');
Route::resource('categories', 'Api\ApiCategoryController');
Route::resource('prices',     'Api\ApiPriceController');
Route::resource('shops',      'Api\ApiShopController');
Route::resource('stocks',     'Api\ApiStockController');
Route::resource('kaspi',      'Api\ApiKaspiController');
