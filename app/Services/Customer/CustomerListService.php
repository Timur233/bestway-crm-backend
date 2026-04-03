<?php

namespace App\Services\Customer;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CustomerListService
{
    public function getCustomersPaginated(array $filters = [], int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildBaseQuery($filters)
            ->orderBy(
                $this->resolveSortColumn($filters['sort_by'] ?? null),
                $this->resolveSortDirection($filters['sort_direction'] ?? null)
            )
            ->paginate($perPage, [
                'customers.id as id',
                'customers.customer_name as customer_name',
                'customers.customer_phone as customer_phone',
                'latest_adres.customer_adres as customer_adres',
                'orders_stats.orders_count as orders_count',
                'orders_stats.total_spent as total_spent',
                DB::raw('DATE_ADD(orders_stats.last_order_date, INTERVAL 5 HOUR) as last_order_date'),
            ], 'page', $page)
            ->withQueryString()
            ->through(function ($customer) {
                return (array) $customer;
            });
    }

    public function getSummary(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);

        return [
            'customers_count' => (clone $query)->count('customers.id'),
            'orders_count' => (int) ((clone $query)->sum('orders_stats.orders_count') ?? 0),
            'total_spent' => (float) ((clone $query)->sum('orders_stats.total_spent') ?? 0),
        ];
    }

    private function buildBaseQuery(array $filters = []): Builder
    {
        $latestCustomerAddresses = DB::table('customer_adreses as customer_addresses')
            ->select(
                'customer_addresses.customer_id',
                DB::raw("MAX(CONCAT('г. ', customer_addresses.town, ' ', customer_addresses.street_name, ' дом ', customer_addresses.street_number)) as customer_adres")
            )
            ->groupBy('customer_addresses.customer_id');

        $ordersStats = DB::table('orders')
            ->select(
                'orders.customer_id',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('COALESCE(SUM(orders.order_total), 0) as total_spent'),
                DB::raw('MAX(orders.created_at) as last_order_date')
            )
            ->groupBy('orders.customer_id');

        $query = DB::table('customers')
            ->leftJoinSub($latestCustomerAddresses, 'latest_adres', function ($join) {
                $join->on('latest_adres.customer_id', '=', 'customers.id');
            })
            ->leftJoinSub($ordersStats, 'orders_stats', function ($join) {
                $join->on('orders_stats.customer_id', '=', 'customers.id');
            });

        $queryString = trim((string) ($filters['query'] ?? ''));
        if ($queryString !== '') {
            $query->where(function (Builder $builder) use ($queryString) {
                $builder
                    ->where('customers.id', 'like', "%{$queryString}%")
                    ->orWhere('customers.customer_name', 'like', "%{$queryString}%")
                    ->orWhere('customers.customer_phone', 'like', "%{$queryString}%")
                    ->orWhere('latest_adres.customer_adres', 'like', "%{$queryString}%");
            });
        }

        return $query;
    }

    private function resolveSortColumn(?string $sortBy): string
    {
        $allowed = [
            'id' => 'customers.id',
            'customer_name' => 'customers.customer_name',
            'customer_phone' => 'customers.customer_phone',
            'customer_adres' => 'latest_adres.customer_adres',
            'orders_count' => 'orders_stats.orders_count',
            'total_spent' => 'orders_stats.total_spent',
            'last_order_date' => 'orders_stats.last_order_date',
        ];

        return $allowed[$sortBy ?? ''] ?? 'customers.created_at';
    }

    private function resolveSortDirection(?string $direction): string
    {
        return $direction === 'asc' ? 'asc' : 'desc';
    }
}
