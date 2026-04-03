<?php

namespace App\Services\Product;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProductRemainAdminService
{
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildBaseQuery($filters)
            ->orderBy(
                $this->resolveSortColumn($filters['sort_by'] ?? null),
                $this->resolveSortDirection($filters['sort_direction'] ?? null)
            )
            ->paginate($perPage, [
                'id',
                'title',
                'quantity',
                'sku_list',
            ], 'page', $page)
            ->withQueryString()
            ->through(function ($item) {
                return (array) $item;
            });
    }

    public function getSummary(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);

        return [
            'items_count' => (clone $query)->count('id'),
            'total_quantity' => (int) ((clone $query)->sum('quantity') ?? 0),
        ];
    }

    public function create(array $data): array
    {
        $payload = $this->normalizePayload($data);
        $id = DB::table('product_remainds')->insertGetId($payload);

        return $this->findOrFail($id);
    }

    public function update(int $id, array $data): array
    {
        $payload = $this->normalizePayload($data);

        DB::table('product_remainds')
            ->where('id', $id)
            ->update($payload);

        return $this->findOrFail($id);
    }

    private function buildBaseQuery(array $filters = []): Builder
    {
        $query = DB::table('product_remainds');

        $queryString = trim((string) ($filters['query'] ?? ''));
        if ($queryString !== '') {
            $query->where(function (Builder $builder) use ($queryString) {
                $builder
                    ->where('id', 'like', "%{$queryString}%")
                    ->orWhere('title', 'like', "%{$queryString}%")
                    ->orWhere('sku_list', 'like', "%{$queryString}%");
            });
        }

        return $query;
    }

    private function resolveSortColumn(?string $sortBy): string
    {
        $allowed = [
            'id' => 'id',
            'title' => 'title',
            'quantity' => 'quantity',
            'sku_list' => 'sku_list',
        ];

        return $allowed[$sortBy ?? ''] ?? 'title';
    }

    private function resolveSortDirection(?string $direction): string
    {
        return $direction === 'asc' ? 'asc' : 'desc';
    }

    private function normalizePayload(array $data): array
    {
        return [
            'title' => trim($data['title']),
            'quantity' => (int) $data['quantity'],
            'sku_list' => $this->normalizeSkuList($data['sku_list']),
        ];
    }

    private function normalizeSkuList(string $skuList): string
    {
        $items = preg_split('/[\r\n,;]+/', $skuList) ?: [];
        $items = array_filter(array_map(static function ($item) {
            return trim($item);
        }, $items));

        return implode(', ', array_values(array_unique($items)));
    }

    private function findOrFail(int $id): array
    {
        $item = DB::table('product_remainds')
            ->where('id', $id)
            ->first(['id', 'title', 'quantity', 'sku_list']);

        abort_if($item === null, 404);

        return (array) $item;
    }
}
