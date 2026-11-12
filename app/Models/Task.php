<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    public const CATEGORIES = [
        'gst' => 'GST Compliance',
        'itr' => 'ITR Filing',
        'tds' => 'TDS Compliance',
        'audit' => 'Audit & Assurance',
        'roc' => 'ROC / MCA',
        'advisory' => 'Tax Advisory',
        'billing' => 'Billing & Accounts',
        'general' => 'General Practice',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const STATUSES = [
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'under_review' => 'Under Review',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'title',
        'description',
        'client_id',
        'assigned_to',
        'created_by',
        'category',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst($this->priority);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->due_date && $this->due_date->isPast();
    }
}

