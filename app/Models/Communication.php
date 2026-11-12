<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Communication extends Model
{
    public const CHANNELS = [
        'portal' => 'Client Portal Message',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp Business API',
        'call' => 'Phone Call Log',
        'meeting' => 'Meeting Note',
    ];

    protected $fillable = [
        'client_id',
        'user_id',
        'sender_type',
        'channel',
        'subject',
        'message',
        'status',
        'meta',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channelLabel(): string
    {
        return self::CHANNELS[$this->channel] ?? ucfirst($this->channel);
    }
}

