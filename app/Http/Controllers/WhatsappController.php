<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WhatsappAdminService;
use App\Services\WhatsApp\WhatsappBotEngine;
use App\Services\WhatsApp\WhatsappConversationService;
use Illuminate\Validation\Rule;
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
            'botBuilderPageEndpoint' => route('whatsapp.bot-builder'),
            'botStepsEndpoint' => route('whatsapp.bot-steps'),
            'logoutEndpoint' => route('logout'),
        ]);
    }

    public function botBuilder()
    {
        return view('whatsapp-bot-builder', [
            'currentUser' => auth()->user(),
            'whatsappPageEndpoint' => route('whatsapp.index'),
            'botStepsEndpoint' => route('whatsapp.bot-steps'),
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

    public function botSteps(): JsonResponse
    {
        return response()->json([
            'data' => $this->whatsappAdminService->getBotSteps(),
        ]);
    }

    public function storeBotStep(Request $request): JsonResponse
    {
        $validated = $this->validateBotStep($request, null);

        $step = $this->whatsappAdminService->createBotStep([
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'reply_text' => $validated['reply_text'],
            'trigger_keywords' => $this->normalizeStringList($validated['trigger_keywords'] ?? []),
            'options' => $this->normalizeOptions($validated['options'] ?? []),
            'fallback_step_slug' => $validated['fallback_step_slug'] ?: null,
            'is_entry' => (bool) $validated['is_entry'],
            'transfer_to_manager' => (bool) $validated['transfer_to_manager'],
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => $validated['sort_order'],
        ]);

        if ($step->is_entry) {
            $step->newQuery()
                ->where('id', '!=', $step->id)
                ->where('is_entry', true)
                ->update(['is_entry' => false]);
        }

        return response()->json([
            'data' => $this->whatsappAdminService->getBotSteps(),
            'selected_id' => $step->id,
        ]);
    }

    public function updateBotStep(Request $request, int $stepId): JsonResponse
    {
        $step = $this->whatsappAdminService->findBotStepOrFail($stepId);
        $validated = $this->validateBotStep($request, $stepId);

        $step->forceFill([
            'title' => $validated['title'],
            'reply_text' => $validated['reply_text'],
            'trigger_keywords' => $this->normalizeStringList($validated['trigger_keywords'] ?? []),
            'options' => $this->normalizeOptions($validated['options'] ?? []),
            'fallback_step_slug' => $validated['fallback_step_slug'] ?: null,
            'is_entry' => (bool) $validated['is_entry'],
            'transfer_to_manager' => (bool) $validated['transfer_to_manager'],
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => $validated['sort_order'],
        ])->save();

        if ($step->is_entry) {
            $step->newQuery()
                ->where('id', '!=', $step->id)
                ->where('is_entry', true)
                ->update(['is_entry' => false]);
        }

        return response()->json([
            'data' => $this->whatsappAdminService->getBotSteps(),
            'selected_id' => $step->id,
        ]);
    }

    private function validateBotStep(Request $request, ?int $stepId): array
    {
        $stepSlugs = $this->whatsappAdminService->getBotSteps()
            ->when($stepId !== null, fn ($collection) => $collection->where('id', '!=', $stepId))
            ->pluck('slug')
            ->all();

        return $request->validate([
            'slug' => [
                $stepId === null ? 'required' : 'sometimes',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                Rule::notIn($stepSlugs),
            ],
            'title' => ['required', 'string', 'max:255'],
            'reply_text' => ['required', 'string', 'max:10000'],
            'trigger_keywords' => ['nullable', 'array'],
            'trigger_keywords.*' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'array'],
            'options.*.keywords' => ['nullable', 'array'],
            'options.*.keywords.*' => ['nullable', 'string', 'max:255'],
            'options.*.next_step_slug' => ['nullable', 'string', Rule::in($this->whatsappAdminService->getBotSteps()->pluck('slug')->all())],
            'fallback_step_slug' => ['nullable', 'string', Rule::in($this->whatsappAdminService->getBotSteps()->pluck('slug')->all())],
            'is_entry' => ['required', 'boolean'],
            'transfer_to_manager' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);
    }

    private function normalizeStringList(array $items): array
    {
        return array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            $items
        ), static fn (string $item) => $item !== ''));
    }

    private function normalizeOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $option) {
            $keywords = $this->normalizeStringList((array) ($option['keywords'] ?? []));
            $nextStepSlug = trim((string) ($option['next_step_slug'] ?? ''));

            if ($nextStepSlug === '' || count($keywords) === 0) {
                continue;
            }

            $normalized[] = [
                'keywords' => $keywords,
                'next_step_slug' => $nextStepSlug,
            ];
        }

        return $normalized;
    }
}
