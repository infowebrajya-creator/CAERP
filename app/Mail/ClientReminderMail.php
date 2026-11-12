<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\FirmSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Client $client,
        public readonly string $mailSubject,
        public readonly string $messageBody,
        public readonly string $firmName = 'WebRajya Accounting & Advisory'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client_reminder',
            with: [
                'client' => $this->client,
                'messageBody' => $this->messageBody,
                'firmName' => FirmSetting::getValue('firm_name', $this->firmName),
            ],
        );
    }
}

