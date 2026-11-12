<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItrFiling extends Model
{
    public const RETURN_TYPES = ['ITR-1', 'ITR-2', 'ITR-3', 'ITR-4', 'ITR-5', 'ITR-6', 'ITR-7'];
    public const CATEGORIES = ['Individual', 'HUF', 'Partnership Firm', 'LLP', 'Private Limited Company', 'Trust / AOP'];

    public const DOCUMENT_STATUSES = [
        'pending' => 'Pending',
        'partial' => 'Partial',
        'complete' => 'Complete',
    ];

    public const FILING_STATUSES = [
        'not_started' => 'Not Started',
        'documents_pending' => 'Documents Pending',
        'computation_ready' => 'Computation Ready',
        'in_progress' => 'In Progress',
        'filed' => 'Filed',
        'verified' => 'E-Verified',
        'overdue' => 'Overdue',
    ];

    public const REFUND_STATUSES = [
        'not_applicable' => 'Not Applicable',
        'pending' => 'Pending',
        'processed' => 'Processed',
        'issued' => 'Refund Issued',
        'adjusted' => 'Adjusted Against Demand',
    ];

    protected $fillable = [
        'client_id',
        'assessment_year',
        'financial_year',
        'itr_category',
        'return_type',
        'assigned_to',
        'document_status',
        'filing_status',
        'due_date',
        'filing_date',
        'acknowledgement_number',
        'refund_status',
        'refund_amount',
        'pending_documents_checklist',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'filing_date' => 'date',
            'refund_amount' => 'decimal:2',
            'pending_documents_checklist' => 'array',
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

    public function filingStatusLabel(): string
    {
        return self::FILING_STATUSES[$this->filing_status] ?? ucfirst($this->filing_status);
    }

    public function refundStatusLabel(): string
    {
        return self::REFUND_STATUSES[$this->refund_status] ?? ucfirst($this->refund_status);
    }
}

