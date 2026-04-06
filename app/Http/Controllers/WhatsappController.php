<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WhatsappAdminService;
use App\Services\WhatsApp\WhatsappBotEngine;
use App\Services\WhatsApp\WhatsappConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function __construct(
        private WhatsappAdminService $whatsappAdminService,
        private WhatsappConversationService $whatsappConversationService,
        private WhatsappBotEngine $whatsappBotEngine
    ) {
    }

    public function index()
    {
        return view('whatsapp', [
            'currentUser' => auth()->user(),
            'conversationsEndpoint' => route('whatsapp.conversations'),
            'logoutEndpoint' => route('logout'),
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:500'],
            'query' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:bot,manager,closed'],
        ]);

        $filters = [
            'query' => $validated['query'] ?? null,
            'status' => $validated['status'] ?? null,
        ];

        $conversations = $this->whatsappAdminService->getConversationsPaginated(
            $filters,
            $validated['page'] ?? 1,
            $validated['per_page'] ?? 25
        );

        return response()->json([
            'data' => $conversations->items(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
                'from' => $conversations->firstItem(),
                'to' => $conversations->lastItem(),
            ],
            'summary' => $this->whatsappAdminService->getSummary($filters),
        ]);
    }

    public function messages(int $conversationId): JsonResponse
    {
        return response()->json([
            'data' => $this->whatsappAdminService->getMessages($conversationId),
        ]);
    }

    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = $this->whatsappAdminService->findConversationOrFail($conversationId);
        $contact = $this->whatsappAdminService->findContactOrFail($conversation->contact_id);

        $this->whatsappConversationService->markManagerRequested($conversation);
        $conversation->forceFill([
            'manager_id' => auth()->id(),
        ])->save();

        $this->whatsappBotEngine->sendMessage(
            $conversation,
            $contact,
            $validated['message'],
            auth()->id()
        );

        return response()->json([
            'success' => true,
        ]);
    }

    public function switchMode(Request $request, int $conversationId): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:bot,manager'],
        ]);

        $conversation = $this->whatsappAdminService->findConversationOrFail($conversationId);

        if ($validated['mode'] === 'manager') {
            $this->whatsappConversationService->markManagerRequested($conversation);
            $conversation->forceFill([
                'manager_id' => auth()->id(),
            ])->save();
        } else {
            $this->whatsappConversationService->markBotMode($conversation);
        }

        return response()->json([
            'data' => $conversation->fresh(['contact:id,name,phone,chat_id', 'manager:id,name']),
        ]);
    }
}
