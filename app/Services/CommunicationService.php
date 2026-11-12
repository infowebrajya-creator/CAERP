<?php

namespace App\Services;

use App\Mail\ClientReminderMail;
use App\Models\Client;
use App\Models\Communication;
use App\Notifications\PracticeAlertNotification;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use Illuminate\Support\Facades\Mail;

/**
 * Dispatches and logs client communications via Portal, Email, Call/Meeting logs, and WhatsApp Business API.
 */
class CommunicationService
{
    public function __construct(
        private readonly WhatsAppProviderInterface $whatsApp,
        private readonly ActivityLogger $logger
    ) {}

    public function send(Client $client, array $data, int $userId, string $senderType = 'staff'): Communication
    {
        $channel = $data['channel'];
        $subject = $data['subject'] ?? null;
        $message = $data['message'];
        $status = 'logged';
        $meta = [];

        if ($channel === 'email') {
            if (! empty($client->email)) {
                try {
                    Mail::to($client->email)->send(new ClientReminderMail(
                        $client,
                        $subject ?: 'Notification from Your CA Firm',
                        $message
                    ));
                    $status = 'sent';
                    $meta['recipient'] = $client->email;
                } catch (\Throwable $e) {
                    $status = 'failed';
                    $meta['error'] = $e->getMessage();
                }
            } else {
                $status = 'failed';
                $meta['error'] = 'Client email address is missing.';
            }
        } elseif ($channel === 'whatsapp') {
            $result = $this->whatsApp->sendTextMessage((string) $client->mobile, $message);
            $status = $result['status'];
            $meta = [
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'provider_note' => $result['message'],
            ];
        } elseif ($channel === 'portal') {
            $status = 'sent';
        }

        $comm = Communication::create([
            'client_id' => $client->id,
            'user_id' => $userId,
            'sender_type' => $senderType,
            'channel' => $channel,
            'subject' => $subject,
            'message' => $message,
            'status' => $status,
            'meta' => empty($meta) ? null : $meta,
            'sent_at' => now(),
        ]);

        // Notify client portal user if staff sent a portal/email/whatsapp message
        if ($senderType === 'staff' && $client->user) {
            $client->user->notify(new PracticeAlertNotification(
                $subject ?: 'New Message from CA Firm',
                mb_strimwidth($message, 0, 140, '...'),
                'communication',
                route('portal.messages')
            ));
        } elseif ($senderType === 'client' && $client->assignedCa) {
            $client->assignedCa->notify(new PracticeAlertNotification(
                "Message from {$client->name}",
                mb_strimwidth($message, 0, 140, '...'),
                'communication',
                route('clients.show', ['client' => $client->id, 'tab' => 'communication'])
            ));
        }

        $this->logger->log(
            'communication',
            $channel,
            "{$senderType} communication via {$channel} for {$client->name} (status: {$status})",
            $comm
        );

        return $comm;
    }
}

