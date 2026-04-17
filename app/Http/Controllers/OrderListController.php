<?php

namespace App\Http\Controllers;

use App\Services\Order\OrderListService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderListController extends Controller
{
    public function __construct(private OrderListService $orderListService)
    {
    }

    public function index()
    {
        return view('orderlist', [
            'currentUser' => auth()->user(),
            'ordersEndpoint' => route('order-list.orders'),
            'logoutEndpoint' => route('logout'),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:500'],
            'query' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ]);

        $filters = [
            'query' => $validated['query'] ?? null,
            'status' => $validated['status'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? null,
            'sort_direction' => $validated['sort_direction'] ?? null,
        ];

        $orders = $this->orderListService->getOrdersPaginated(
            $filters,
            $validated['page'] ?? 1,
            $validated['per_page'] ?? 25
        );
        $summary = $this->orderListService->getSummary($filters);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
            'summary' => $summary,
            'filters' => [
                'applied' => $filters,
                'statuses' => $this->orderListService->getAvailableStatuses(),
            ],
        ]);
    }

    public function qr(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $contactUrl = 'https://pay.kaspi.kz/chat?threadId=' . urlencode($validated['code']) . '&type=CLIENT_SELLER_BY_ORDER&from=orderInfo_pay_web';
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 8,
            'imageTransparent' => false,
        ]);

        $image = (new QRCode($options))->render($contactUrl);

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
