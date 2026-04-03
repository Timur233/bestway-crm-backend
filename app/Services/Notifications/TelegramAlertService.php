<?php

namespace App\Services\Notifications;

class TelegramAlertService
{
    public function sendMessage(string $message, array $buttons = []): void
    {
        $query = [
            'chat_id' => config('services.telegram.chat_id'),
            'parse_mode' => 'HTML',
            'text' => $message,
        ];

        if (!empty($buttons)) {
            $query['reply_markup'] = json_encode([
                'inline_keyboard' => $buttons,
            ]);
        }

        file_get_contents(
            sprintf(
                'https://api.telegram.org/bot%s/sendMessage?%s',
                config('services.telegram.bot_token'),
                http_build_query($query)
            )
        );
    }
}
