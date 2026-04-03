<?php

namespace App\Services\Order;

use App\Models\Customer;
use App\Models\CustomerAdreses;
use App\Models\CustomerFields;
use App\Models\Order;
use App\Models\OrderFields;

class OrderPersistenceService
{
    public function persist(array $orderData, int $statusId): array
    {
        $existingOrder = Order::where('order_number', '=', $orderData['order_number'])->first();
        $customer = $this->createOrUpdateCustomer($orderData['customer']);

        if ($existingOrder) {
            $existingOrder->update([
                'status_id' => $statusId,
                'customer_id' => $customer['id'],
                'order_total' => $orderData['order_total'],
                'order_description' => $orderData['entries_in_line'],
            ]);

            $this->replaceOrderFields($existingOrder->id, $customer['adres_id'], $orderData['order_fields']);

            return [
                'order' => $existingOrder->fresh(),
                'created' => false,
                'customer' => $customer,
            ];
        }

        $newOrder = new Order();
        $newOrder->order_number = $orderData['order_number'];
        $newOrder->order_description = $orderData['entries_in_line'];
        $newOrder->status_id = $statusId;
        $newOrder->customer_id = $customer['id'];
        $newOrder->order_total = $orderData['order_total'];
        $newOrder->save();

        $this->replaceOrderFields($newOrder->id, $customer['adres_id'], $orderData['order_fields']);

        return [
            'order' => $newOrder->fresh(),
            'created' => true,
            'customer' => $customer,
        ];
    }

    private function createOrUpdateCustomer(array $customerData): array
    {
        if ($customerData['customer_phone'] === '') {
            return ['id' => null, 'adres_id' => null];
        }

        $existingCustomer = Customer::where('customer_phone', '=', $customerData['customer_phone'])->first();

        if ($existingCustomer) {
            $customerAdres = $this->createCustomerAdres($existingCustomer->id, $customerData['cutomer_adres']);

            $this->replaceCustomerFields(
                $existingCustomer->id,
                $customerAdres,
                $customerData['customer_fields']
            );

            return ['id' => $existingCustomer->id, 'adres_id' => $customerAdres];
        }

        $newCustomer = new Customer();
        $newCustomer->customer_name = $customerData['customer_name'];
        $newCustomer->customer_phone = $customerData['customer_phone'];
        $newCustomer->save();

        $customerAdres = $this->createCustomerAdres($newCustomer->id, $customerData['cutomer_adres']);

        $this->replaceCustomerFields(
            $newCustomer->id,
            $customerAdres,
            $customerData['customer_fields']
        );

        return ['id' => $newCustomer->id, 'adres_id' => $customerAdres];
    }

    private function createCustomerAdres(int $customerId, array $adres): ?int
    {
        if ($adres === []) {
            return null;
        }

        if ($adres['town'] === '' || $adres['street_name'] === '' || $adres['street_number'] === '') {
            return null;
        }

        $existingAdres = CustomerAdreses::where([
            ['customer_id', '=', $customerId],
            ['town', '=', $adres['town']],
            ['street_name', '=', $adres['street_name']],
            ['street_number', '=', $adres['street_number']],
            ['apartment', '=', $adres['apartment']],
        ])->first();

        if ($existingAdres) {
            return $existingAdres->id;
        }

        $newAdres = new CustomerAdreses();
        $newAdres->customer_id = $customerId;
        $newAdres->street_name = $adres['street_name'];
        $newAdres->street_number = $adres['street_number'];
        $newAdres->town = $adres['town'];
        $newAdres->district = $adres['district'];
        $newAdres->building = $adres['building'];
        $newAdres->apartment = $adres['apartment'];
        $newAdres->latitude = $adres['latitude'];
        $newAdres->longitude = $adres['longitude'];
        $newAdres->save();

        return $newAdres->id;
    }

    private function replaceCustomerFields(int $customerId, ?int $adresId, array $fields): void
    {
        $customerFields = [];

        foreach ($fields as $field) {
            $customerFields[] = array_merge(['customer_id' => $customerId], $field);
        }

        if ($adresId) {
            $customerFields[] = [
                'customer_id' => $customerId,
                'name' => 'Актуальный адрес',
                'slug' => 'actual_adres',
                'value' => $adresId,
            ];
        }

        CustomerFields::where('customer_id', $customerId)->delete();
        CustomerFields::insert($customerFields);
    }

    private function replaceOrderFields(int $orderId, ?int $adresId, array $fields): void
    {
        $orderFields = [];

        foreach ($fields as $field) {
            $orderFields[] = array_merge(['order_id' => $orderId], $field);
        }

        if ($adresId !== null) {
            $orderFields[] = [
                'order_id' => $orderId,
                'field_name' => 'Адрес доставки',
                'field_slug' => 'order_delivery_adres_id',
                'field_value' => $adresId,
            ];
        }

        OrderFields::where('order_id', $orderId)->delete();
        OrderFields::insert($orderFields);
    }
}
