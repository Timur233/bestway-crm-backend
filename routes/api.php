<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiKaspiController;
use App\Http\Controllers\Api\ApiTestController;

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

Route::get('/kaspi', [ApiKaspiController::class, 'index']);
Route::get('/kaspi/all', [ApiKaspiController::class, 'all']);
Route::get('/test', [ApiTestController::class, 'test']);
Route::post('/webhooks/green-api', 'Webhook\GreenApiWebhookController');
