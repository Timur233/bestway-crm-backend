<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

class TelegramAlertService
{
    public function sendMessage(string $message, array $buttons = []): void
    {
        $payload = [
            'chat_id' => config('services.telegram.chat_id'),
            'parse_mode' => 'HTML',
            'text' => $message,
        ];

        if (!empty($buttons)) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $buttons,
            ]);
        }

        $this->sendRequest('sendMessage', $payload);
    }

    public function sendPhoto(string $photoUrl, ?string $caption = null): void
    {
        $payload = [
            'chat_id' => config('services.telegram.chat_id'),
            'photo' => $photoUrl,
        ];

        if ($caption !== null && $caption !== '') {
            $payload['caption'] = $caption;
        }

        $this->sendRequest('sendPhoto', $payload);
    }

    private function sendRequest(string $method, array $payload): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ]);

        $response = file_get_contents(
            sprintf(
                'https://api.telegram.org/bot%s/%s',
                config('services.telegram.bot_token'),
                $method
            ),
            false,
            $context
        );

        if ($response === false || $response === null || $response === '') {
            Log::error('Telegram request returned an empty response.', [
                'method' => $method,
            ]);

            return;
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded) || ($decoded['ok'] ?? false) !== true) {
            Log::error('Telegram request failed.', [
                'method' => $method,
                'response' => $response,
            ]);
        }
    }
}
