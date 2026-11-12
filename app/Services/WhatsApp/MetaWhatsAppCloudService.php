<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integration-ready WhatsApp Business Cloud API provider.
 * Never fakes message delivery: requires valid WHATSAPP_* credentials to dispatch live messages.
 */
class MetaWhatsAppCloudService implements WhatsAppProviderInterface
{
    public function isConfigured(): bool
    {
        return (bool) config('services.whatsapp.enabled')
            && ! empty(config('services.whatsapp.phone_number_id'))
            && ! empty(config('services.whatsapp.access_token'));
    }

    public function sendTextMessage(string $recipientPhone, string $message): array
    {
        $normalizedPhone = preg_replace('/[^\d+]/', '', $recipientPhone) ?: '';
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => ltrim($normalizedPhone, '+'),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ];

        if (! $this->isConfigured()) {
            return [
                'status' => 'not_configured',
                'provider_message_id' => null,
                'message' => 'WhatsApp Business API credentials are not configured. Message logged as pending integration setup.',
                'payload' => $payload,
            ];
        }

        if (empty($normalizedPhone)) {
            return [
                'status' => 'failed',
                'provider_message_id' => null,
                'message' => 'Client mobile number is missing or invalid.',
                'payload' => $payload,
            ];
        }

        $endpoint = sprintf(
            '%s/%s/%s/messages',
            rtrim((string) config('services.whatsapp.base_url'), '/'),
            config('services.whatsapp.api_version', 'v20.0'),
            config('services.whatsapp.phone_number_id')
        );

        try {
            $response = Http::withToken((string) config('services.whatsapp.access_token'))
                ->timeout(10)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                return [
                    'status' => 'sent',
                    'provider_message_id' => $response->json('messages.0.id'),
                    'message' => 'Dispatched via WhatsApp Business Cloud API.',
                    'payload' => $payload,
                ];
            }

            return [
                'status' => 'failed',
                'provider_message_id' => null,
                'message' => 'WhatsApp API error: '.($response->json('error.message') ?? $response->status()),
                'payload' => $payload,
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp dispatch exception', ['error' => $e->getMessage()]);

            return [
                'status' => 'failed',
                'provider_message_id' => null,
                'message' => 'WhatsApp dispatch failed: '.$e->getMessage(),
                'payload' => $payload,
            ];
        }
    }
}

