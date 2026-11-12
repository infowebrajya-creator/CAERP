<?php

namespace App\Services\WhatsApp;

interface WhatsAppProviderInterface
{
    public function isConfigured(): bool;

    /**
     * @return array{status: string, provider_message_id?: string|null, message: string, payload?: array}
     */
    public function sendTextMessage(string $recipientPhone, string $message): array;
}
