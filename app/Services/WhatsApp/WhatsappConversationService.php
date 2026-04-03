<?php

namespace App\Services\Whatsapp;

use App\Models\WhatsappContact;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Carbon\Carbon;

class WhatsappConversationService
{
    public function findOrCreateContact(string $chatId, string $phone, ?string $name = null): WhatsappContact
    {
        $contact = WhatsappContact::query()->firstOrCreate(
            ['chat_id' => $chatId],
            [
                'phone' => $phone,
                'name' => $name,
            ]
        );

        $contact->forceFill([
            'phone' => $phone,
            'name' => $name ?: $contact->name,
            'last_message_at' => now(),
        ])->save();

        return $contact;
    }

    public function findOrCreateConversation(WhatsappContact $contact): WhatsappConversation
    {
        $conversation = WhatsappConversation::query()
            ->where('contact_id', $contact->id)
            ->whereNull('closed_at')
            ->latest('id')
            ->first();

        if ($conversation !== null) {
            $conversation->forceFill([
                'last_message_at' => now(),
            ])->save();

            return $conversation;
        }

        return WhatsappConversation::create([
            'contact_id' => $contact->id,
            'status' => 'bot',
            'current_step_slug' => null,
            'last_message_at' => now(),
        ]);
    }

    public function storeIncomingMessage(
        WhatsappConversation $conversation,
        WhatsappContact $contact,
        ?string $externalId,
        string $messageType,
        ?string $body,
        array $payload
    ): WhatsappMessage {
        return WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'external_id' => $externalId,
            'direction' => 'incoming',
            'message_type' => $messageType,
            'body' => $body,
            'payload' => $payload,
            'sent_at' => Carbon::now(),
        ]);
    }

    public function storeOutgoingMessage(
        WhatsappConversation $conversation,
        WhatsappContact $contact,
        ?int $userId,
        ?string $externalId,
        string $body,
        array $payload = []
    ): WhatsappMessage {
        return WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'user_id' => $userId,
            'external_id' => $externalId,
            'direction' => 'outgoing',
            'message_type' => 'text',
            'body' => $body,
            'payload' => $payload,
            'sent_at' => Carbon::now(),
        ]);
    }

    public function markManagerRequested(WhatsappConversation $conversation): WhatsappConversation
    {
        $conversation->forceFill([
            'status' => 'manager',
            'manager_requested_at' => now(),
            'unresolved_count' => 0,
        ])->save();

        return $conversation;
    }

    public function markBotMode(WhatsappConversation $conversation): WhatsappConversation
    {
        $conversation->forceFill([
            'status' => 'bot',
            'manager_id' => null,
            'manager_requested_at' => null,
            'unresolved_count' => 0,
        ])->save();

        return $conversation;
    }
}
