<?php

namespace App\Http\Controllers;

use App\Services\Product\ProductRemainAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductRemainController extends Controller
{
    public function __construct(private ProductRemainAdminService $productRemainAdminService)
    {
    }

    public function index()
    {
        return view('productremains', [
            'currentUser' => auth()->user(),
            'remainsEndpoint' => route('product-remains.items'),
            'logoutEndpoint' => route('logout'),
        ]);
    }

    public function items(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:500'],
            'query' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ]);

        $filters = [
            'query' => $validated['query'] ?? null,
            'sort_by' => $validated['sort_by'] ?? null,
            'sort_direction' => $validated['sort_direction'] ?? null,
        ];

        $items = $this->productRemainAdminService->getPaginated(
            $filters,
            $validated['page'] ?? 1,
            $validated['per_page'] ?? 25
        );

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
            'summary' => $this->productRemainAdminService->getSummary($filters),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRemain($request);

        $item = $this->productRemainAdminService->create($validated);

        return response()->json([
            'data' => $item,
            'message' => 'Остаток создан.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $this->validateRemain($request);

        $item = $this->productRemainAdminService->update($id, $validated);

        return response()->json([
            'data' => $item,
            'message' => 'Остаток обновлен.',
        ]);
    }

    private function validateRemain(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0'],
            'sku_list' => ['required', 'string', 'max:5000'],
        ]);
    }
}
