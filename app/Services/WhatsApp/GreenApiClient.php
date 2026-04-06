<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class GreenApiClient
{
    public function sendTextMessage(string $chatId, string $message): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->post($this->endpoint('sendMessage'), [
                'chatId' => $chatId,
                'message' => $message,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('GREEN-API sendMessage failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    private function endpoint(string $method): string
    {
        return sprintf(
            '/waInstance%s/%s/%s',
            config('services.green_api.instance_id'),
            $method,
            config('services.green_api.api_token')
        );
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.green_api.base_url'), '/');
    }
}
