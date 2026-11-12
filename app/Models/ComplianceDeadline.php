<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceDeadline extends Model
{
    public const TYPES = [
        'gst' => 'GST',
        'itr' => 'ITR',
        'tds' => 'TDS',
        'advance_tax' => 'Advance Tax',
        'roc' => 'ROC Compliance',
        'audit' => 'Audit',
        'custom' => 'Custom Reminder',
    ];

    public const STATUSES = [
        'upcoming' => 'Upcoming',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'overdue' => 'Overdue',
    ];

    protected $fillable = [
        'title',
        'compliance_type',
        'client_id',
        'assigned_to',
        'compliance_rule_id',
        'due_date',
        'reminder_date',
        'status',
        'priority',
        'description',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'reminder_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ComplianceRule::class, 'compliance_rule_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->compliance_type] ?? strtoupper($this->compliance_type);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}

