<?php

namespace App\Services\Order;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderListService
{
    public function getOrdersPaginated(array $filters = [], int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);

        return $query
            ->orderBy(
                $this->resolveSortColumn($filters['sort_by'] ?? null),
                $this->resolveSortDirection($filters['sort_direction'] ?? null)
            )
            ->paginate($perPage, [
                'orders.id as id',
                DB::raw('DATE_ADD(orders.created_at, INTERVAL 5 HOUR) as order_date'),
                'orders.order_description as description',
                'orders.order_total as total',
                'kaspi_codes.kaspi_code as kaspi_code',
                'shops.title as shop_title',
                'statuses.status_description as status',
                'customers.customer_name as customer_name',
                'customers.customer_phone as customer_phone',
                'latest_adres.customer_adres as customer_adres',
            ], 'page', $page)
            ->withQueryString()
            ->through(function ($order) {
                return (array) $order;
            });
    }

    public function getSummary(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);

        return [
            'orders_count' => (clone $query)->count('orders.id'),
            'total_amount' => (float) ((clone $query)->sum('orders.order_total') ?? 0),
        ];
    }

    public function getAvailableStatuses(): Collection
    {
        return DB::table('order_statuses')
            ->orderBy('status_description')
            ->pluck('status_description')
            ->filter()
            ->values();
    }

    private function buildBaseQuery(array $filters = []): Builder
    {
        $latestCustomerAddresses = DB::table('customer_adreses as customer_addresses')
            ->select(
                'customer_addresses.customer_id',
                DB::raw("MAX(CONCAT('г. ', customer_addresses.town, ' ', customer_addresses.street_name, ' дом ', customer_addresses.street_number)) as customer_adres")
            )
            ->groupBy('customer_addresses.customer_id');

        $kaspiCodes = DB::table('order_fields as order_field_codes')
            ->select(
                'order_field_codes.order_id',
                DB::raw('MAX(order_field_codes.field_value) as kaspi_code')
            )
            ->where('order_field_codes.field_slug', '=', 'kaspi_code')
            ->groupBy('order_field_codes.order_id');

        $shopIds = DB::table('order_fields as order_field_shop_ids')
            ->select(
                'order_field_shop_ids.order_id',
                DB::raw('MAX(order_field_shop_ids.field_value) as shop_id')
            )
            ->where('order_field_shop_ids.field_slug', '=', 'order_shop_id')
            ->groupBy('order_field_shop_ids.order_id');

        $query = DB::table('orders')
            ->leftJoin('order_statuses as statuses', 'orders.status_id', '=', 'statuses.id')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->leftJoinSub($latestCustomerAddresses, 'latest_adres', function ($join) {
                $join->on('latest_adres.customer_id', '=', 'customers.id');
            })
            ->leftJoinSub($kaspiCodes, 'kaspi_codes', function ($join) {
                $join->on('kaspi_codes.order_id', '=', 'orders.id');
            })
            ->leftJoinSub($shopIds, 'shop_ids', function ($join) {
                $join->on('shop_ids.order_id', '=', 'orders.id');
            })
            ->leftJoin('kaspi_shops as shops', 'shops.id', '=', DB::raw('CAST(shop_ids.shop_id AS UNSIGNED)'));

        $queryString = trim((string) ($filters['query'] ?? ''));
        if ($queryString !== '') {
            $query->where(function (Builder $builder) use ($queryString) {
                $builder
                    ->where('orders.id', 'like', "%{$queryString}%")
                    ->orWhere('orders.order_description', 'like', "%{$queryString}%")
                    ->orWhere('customers.customer_name', 'like', "%{$queryString}%")
                    ->orWhere('customers.customer_phone', 'like', "%{$queryString}%")
                    ->orWhere('statuses.status_description', 'like', "%{$queryString}%")
                    ->orWhere('kaspi_codes.kaspi_code', 'like', "%{$queryString}%")
                    ->orWhere('shops.title', 'like', "%{$queryString}%")
                    ->orWhere('latest_adres.customer_adres', 'like', "%{$queryString}%");
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('statuses.status_description', '=', $status);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('orders.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('orders.created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function resolveSortColumn(?string $sortBy): string
    {
        $allowed = [
            'id' => 'orders.id',
            'order_date' => 'orders.created_at',
            'description' => 'orders.order_description',
            'total' => 'orders.order_total',
            'kaspi_code' => 'kaspi_codes.kaspi_code',
            'shop_title' => 'shops.title',
            'status' => 'statuses.status_description',
            'customer_name' => 'customers.customer_name',
            'customer_phone' => 'customers.customer_phone',
            'customer_adres' => 'latest_adres.customer_adres',
        ];

        return $allowed[$sortBy ?? ''] ?? 'orders.created_at';
    }

    private function resolveSortDirection(?string $direction): string
    {
        return $direction === 'asc' ? 'asc' : 'desc';
    }
}
