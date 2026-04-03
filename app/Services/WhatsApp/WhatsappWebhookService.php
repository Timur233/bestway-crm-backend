<?php

namespace App\Services\Whatsapp;

use App\Models\WhatsappContact;
use App\Models\WhatsappConversation;

class WhatsappWebhookService
{
    public function __construct(
        private WhatsappConversationService $conversationService,
        private WhatsappBotEngine $botEngine
    ) {
    }

    public function handleIncoming(array $payload): void
    {
        if (!$this->isIncomingMessage($payload)) {
            return;
        }

        $chatId = (string) ($payload['senderData']['chatId'] ?? '');
        if ($chatId === '') {
            return;
        }

        $phone = $this->extractPhone($payload, $chatId);
        $name = $payload['senderData']['senderName'] ?? $payload['senderData']['chatName'] ?? null;
        $messageType = (string) ($payload['messageData']['typeMessage'] ?? 'text');
        $body = $this->extractBody($payload);

        $contact = $this->conversationService->findOrCreateContact($chatId, $phone, $name);
        $conversation = $this->conversationService->findOrCreateConversation($contact);

        $this->conversationService->storeIncomingMessage(
            $conversation,
            $contact,
            $payload['idMessage'] ?? null,
            $messageType,
            $body,
            $payload
        );

        if ($messageType !== 'textMessage' && $messageType !== 'extendedTextMessage') {
            $this->botEngine->sendMessage(
                $conversation,
                $contact,
                "Спасибо! Пока я умею обрабатывать текстовые сообщения. Если нужна помощь, напишите сообщение текстом или укажите 'менеджер'."
            );

            return;
        }

        $this->botEngine->handleIncomingText($conversation, $contact, $body);
    }

    private function isIncomingMessage(array $payload): bool
    {
        $typeWebhook = (string) ($payload['typeWebhook'] ?? '');

        return in_array($typeWebhook, ['incomingMessageReceived', 'incomingMessageReceivedNotification'], true);
    }

    private function extractPhone(array $payload, string $chatId): string
    {
        $sender = (string) ($payload['senderData']['sender'] ?? '');

        if ($sender !== '') {
            return preg_replace('/\D+/', '', $sender) ?: $sender;
        }

        return preg_replace('/\D+/', '', $chatId) ?: $chatId;
    }

    private function extractBody(array $payload): ?string
    {
        $messageData = $payload['messageData'] ?? [];

        return $messageData['textMessageData']['textMessage']
            ?? $messageData['extendedTextMessageData']['text']
            ?? $messageData['caption']
            ?? null;
    }
}
