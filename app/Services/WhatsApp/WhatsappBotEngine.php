<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappBotStep;
use App\Models\WhatsappContact;
use App\Models\WhatsappConversation;

class WhatsappBotEngine
{
    public function __construct(
        private GreenApiClient $greenApiClient,
        private WhatsappConversationService $conversationService
    ) {
    }

    public function handleIncomingText(WhatsappConversation $conversation, WhatsappContact $contact, ?string $text): void
    {
        if ($conversation->status === 'manager') {
            return;
        }

        $normalized = $this->normalizeText($text);
        $currentStep = $this->resolveCurrentStep($conversation);

        if ($currentStep === null) {
            $entryStep = $this->entryStep();
            $this->sendStep($conversation, $contact, $entryStep);

            return;
        }

        $nextStep = $this->resolveNextStep($currentStep, $normalized);

        if ($nextStep === null) {
            $conversation->increment('unresolved_count');

            if ($conversation->unresolved_count >= 2) {
                $managerStep = WhatsappBotStep::query()->where('slug', 'manager')->first();
                if ($managerStep !== null) {
                    $this->sendStep($conversation, $contact, $managerStep);
                }

                return;
            }

            $reply = "Не совсем понял запрос.\n\n" . $currentStep->reply_text;
            $this->sendMessage($conversation, $contact, $reply);

            return;
        }

        $this->sendStep($conversation, $contact, $nextStep);
    }

    public function sendStep(WhatsappConversation $conversation, WhatsappContact $contact, WhatsappBotStep $step): void
    {
        if ($step->transfer_to_manager) {
            $this->conversationService->markManagerRequested($conversation);
        } else {
            $conversation->forceFill([
                'current_step_slug' => $step->slug,
                'unresolved_count' => 0,
                'status' => 'bot',
            ])->save();
        }

        $this->sendMessage($conversation, $contact, $step->reply_text);
    }

    public function sendMessage(WhatsappConversation $conversation, WhatsappContact $contact, string $message, ?int $userId = null): void
    {
        $response = $this->greenApiClient->sendTextMessage($contact->chat_id, $message);

        $this->conversationService->storeOutgoingMessage(
            $conversation,
            $contact,
            $userId,
            $response['idMessage'] ?? null,
            $message,
            $response
        );

        $conversation->forceFill([
            'last_message_at' => now(),
        ])->save();
    }

    private function resolveCurrentStep(WhatsappConversation $conversation): ?WhatsappBotStep
    {
        if ($conversation->current_step_slug === null) {
            return null;
        }

        return WhatsappBotStep::query()
            ->where('slug', $conversation->current_step_slug)
            ->where('is_active', true)
            ->first();
    }

    private function entryStep(): WhatsappBotStep
    {
        return WhatsappBotStep::query()
            ->where('is_entry', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->firstOrFail();
    }

    private function resolveNextStep(WhatsappBotStep $step, string $normalized): ?WhatsappBotStep
    {
        $managerKeywords = ['менеджер', 'оператор', 'человек'];
        if ($this->matchesAny($normalized, $managerKeywords)) {
            return WhatsappBotStep::query()->where('slug', 'manager')->first();
        }

        foreach (($step->options ?? []) as $option) {
            $keywords = (array) ($option['keywords'] ?? []);
            if (!$this->matchesAny($normalized, $keywords)) {
                continue;
            }

            return WhatsappBotStep::query()
                ->where('slug', $option['next_step_slug'] ?? null)
                ->where('is_active', true)
                ->first();
        }

        if ($step->fallback_step_slug !== null) {
            return WhatsappBotStep::query()
                ->where('slug', $step->fallback_step_slug)
                ->where('is_active', true)
                ->first();
        }

        return null;
    }

    private function matchesAny(string $haystack, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, $this->normalizeText((string) $keyword))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(?string $text): string
    {
        return mb_strtolower(trim((string) $text));
    }
}
