<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
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
        try {
            $response = Http::asForm()
                ->withOptions(['connect_timeout' => 10])
                ->timeout(20)
                ->retry(2, 500)
                ->post(sprintf(
                    'https://api.telegram.org/bot%s/%s',
                    config('services.telegram.bot_token'),
                    $method
                ), $payload);
        } catch (\Throwable $exception) {
            Log::error('Telegram request transport failed.', [
                'method' => $method,
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        $body = $response->body();

        if ($body === '') {
            Log::error('Telegram request returned an empty response.', [
                'method' => $method,
                'status' => $response->status(),
            ]);

            return;
        }

        $decoded = json_decode($body, true);

        if (!$response->successful() || !is_array($decoded) || ($decoded['ok'] ?? false) !== true) {
            Log::error('Telegram request failed.', [
                'method' => $method,
                'status' => $response->status(),
                'response' => $body,
            ]);
        }
    }
}
