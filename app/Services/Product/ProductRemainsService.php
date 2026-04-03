<?php

namespace App\Services\Product;

use App\Services\Notifications\TelegramAlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProductRemainsService
{
    public function __construct(private TelegramAlertService $telegramAlertService)
    {
    }

    public function changeRemind(string $sku, int $quantityInOrder): string
    {
        $products = DB::select('SELECT * FROM product_remainds');

        foreach ($products as $product) {
            $skuList = explode(', ', $product->sku_list);

            if (!in_array($sku, $skuList, true)) {
                continue;
            }

            if ($product->quantity < $quantityInOrder) {
                return 'Insufficient quantity.';
            }

            $remainingQuantity = $product->quantity - $quantityInOrder;

            DB::update(
                'UPDATE product_remainds SET quantity = ? WHERE id = ?',
                [$remainingQuantity, $product->id]
            );

            if ($remainingQuantity === 0) {
                $this->telegramAlertService->sendMessage("Cнимаю с продажи $sku");
                $this->deleteProductsFromKaspi($skuList);
            }

            return "Quantity decremented by $quantityInOrder";
        }

        return 'Product not found.';
    }

    private function deleteProductsFromKaspi(array $skuList): void
    {
        foreach ($skuList as $sku) {
            print_r(urlencode($sku));

            $response = Http::get(
                'https://bestway-asia.kz/integration/api/remove_kaspi_sale.php?sku=' . urlencode($sku)
            );

            if ($response->successful()) {
                print_r($response->json());
                continue;
            }

            print_r('Error: ' . $response->status());
        }
    }
}
