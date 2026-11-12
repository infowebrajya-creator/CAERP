<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstReturn extends Model
{
    public const STATUSES = [
        'not_started' => 'Not Started',
        'documents_pending' => 'Documents Pending',
        'in_progress' => 'In Progress',
        'filed' => 'Filed',
        'overdue' => 'Overdue',
    ];

    protected $fillable = [
        'client_id',
        'return_type_id',
        'return_type_code',
        'financial_year',
        'tax_period',
        'period_month',
        'period_year',
        'due_date',
        'status',
        'arn',
        'filing_date',
        'assigned_to',
        'internal_notes',
        'document_checklist',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'filing_date' => 'date',
            'document_checklist' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function returnType(): BelongsTo
    {
        return $this->belongsTo(GstReturnType::class, 'return_type_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }
}

