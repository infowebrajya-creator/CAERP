<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    public const AUDIT_TYPES = [
        'statutory' => 'Statutory Audit',
        'tax_audit' => 'Tax Audit (Sec 44AB)',
        'gst_audit' => 'GST Audit / Reconciliation',
        'internal_audit' => 'Internal Audit',
        'stock_audit' => 'Stock & Inventory Audit',
        'concurrent_audit' => 'Concurrent Audit',
    ];

    public const STATUSES = [
        'planned' => 'Planned',
        'in_progress' => 'In Progress',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'client_id',
        'title',
        'audit_type',
        'financial_year',
        'lead_auditor_id',
        'start_date',
        'due_date',
        'completion_date',
        'status',
        'progress_percent',
        'checklist',
        'observations',
        'review_notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completion_date' => 'date',
            'approved_at' => 'datetime',
            'checklist' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leadAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_auditor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_team_members')
            ->withPivot('role_in_audit')
            ->withTimestamps();
    }

    public function workingPapers(): HasMany
    {
        return $this->hasMany(AuditWorkingPaper::class)->latest();
    }

    public function typeLabel(): string
    {
        return self::AUDIT_TYPES[$this->audit_type] ?? ucfirst(str_replace('_', ' ', $this->audit_type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function recalculateProgress(): int
    {
        $items = $this->checklist ?? [];
        if (empty($items)) {
            return (int) $this->progress_percent;
        }
        $done = collect($items)->where('completed', true)->count();

        return (int) round(($done / count($items)) * 100);
    }
}

