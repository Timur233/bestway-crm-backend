<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappContact;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WhatsappAdminService
{
    public function getConversationsPaginated(array $filters = [], int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        $query = WhatsappConversation::query()
            ->with(['contact:id,name,phone,chat_id', 'manager:id,name'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        $queryString = trim((string) ($filters['query'] ?? ''));
        if ($queryString !== '') {
            $query->where(function (Builder $builder) use ($queryString) {
                $builder
                    ->whereHas('contact', function (Builder $contactQuery) use ($queryString) {
                        $contactQuery
                            ->where('name', 'like', "%{$queryString}%")
                            ->orWhere('phone', 'like', "%{$queryString}%")
                            ->orWhere('chat_id', 'like', "%{$queryString}%");
                    })
                    ->orWhere('status', 'like', "%{$queryString}%");
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getSummary(array $filters = []): array
    {
        $baseQuery = WhatsappConversation::query();

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $baseQuery->where('status', $status);
        }

        return [
            'total_conversations' => (clone $baseQuery)->count(),
            'manager_conversations' => (clone $baseQuery)->where('status', 'manager')->count(),
            'bot_conversations' => (clone $baseQuery)->where('status', 'bot')->count(),
        ];
    }

    public function getMessages(int $conversationId)
    {
        return WhatsappMessage::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('id')
            ->get();
    }

    public function findConversationOrFail(int $id): WhatsappConversation
    {
        return WhatsappConversation::query()
            ->with(['contact:id,name,phone,chat_id', 'manager:id,name'])
            ->findOrFail($id);
    }

    public function findContactOrFail(int $id): WhatsappContact
    {
        return WhatsappContact::query()->findOrFail($id);
    }
}
