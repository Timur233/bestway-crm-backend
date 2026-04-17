<?php

use App\Http\Controllers\CustomerListController;
use App\Http\Controllers\ProductRemainController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderListController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('order-list.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::get('/order-list/qr', [OrderListController::class, 'qr'])
    ->middleware('signed')
    ->name('order-list.qr');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/order-list', [OrderListController::class, 'index'])->name('order-list.index');
    Route::get('/order-list/orders', [OrderListController::class, 'orders'])->name('order-list.orders');
    Route::get('/customer-list', [CustomerListController::class, 'index'])->name('customer-list.index');
    Route::get('/customer-list/customers', [CustomerListController::class, 'customers'])->name('customer-list.customers');
    Route::get('/product-remains', [ProductRemainController::class, 'index'])->name('product-remains.index');
    Route::get('/product-remains/items', [ProductRemainController::class, 'items'])->name('product-remains.items');
    Route::post('/product-remains/items', [ProductRemainController::class, 'store'])->name('product-remains.store');
    Route::put('/product-remains/items/{id}', [ProductRemainController::class, 'update'])->name('product-remains.update');
    Route::get('/whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index');
    Route::get('/whatsapp/bot-builder', [WhatsappController::class, 'botBuilder'])->name('whatsapp.bot-builder');
    Route::get('/whatsapp/conversations', [WhatsappController::class, 'conversations'])->name('whatsapp.conversations');
    Route::get('/whatsapp/conversations/{conversationId}/messages', [WhatsappController::class, 'messages'])->name('whatsapp.messages');
    Route::post('/whatsapp/conversations/{conversationId}/send-message', [WhatsappController::class, 'sendMessage'])->name('whatsapp.send-message');
    Route::post('/whatsapp/conversations/{conversationId}/switch-mode', [WhatsappController::class, 'switchMode'])->name('whatsapp.switch-mode');
    Route::get('/whatsapp/bot-steps', [WhatsappController::class, 'botSteps'])->name('whatsapp.bot-steps');
    Route::post('/whatsapp/bot-steps', [WhatsappController::class, 'storeBotStep'])->name('whatsapp.bot-steps.store');
    Route::put('/whatsapp/bot-steps/{stepId}', [WhatsappController::class, 'updateBotStep'])->name('whatsapp.bot-steps.update');
});
