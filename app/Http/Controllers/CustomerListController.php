<?php

namespace App\Http\Controllers;

use App\Services\Customer\CustomerListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerListController extends Controller
{
    public function __construct(private CustomerListService $customerListService)
    {
    }

    public function index()
    {
        return view('customerlist', [
            'currentUser' => auth()->user(),
            'customersEndpoint' => route('customer-list.customers'),
            'logoutEndpoint' => route('logout'),
        ]);
    }

    public function customers(Request $request): JsonResponse
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

        $customers = $this->customerListService->getCustomersPaginated(
            $filters,
            $validated['page'] ?? 1,
            $validated['per_page'] ?? 25
        );
        $summary = $this->customerListService->getSummary($filters);

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
            'summary' => $summary,
        ]);
    }
}
