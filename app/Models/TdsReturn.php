<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdsReturn extends Model
{
    public const QUARTERS = ['Q1', 'Q2', 'Q3', 'Q4'];
    public const FORM_TYPES = ['24Q', '26Q', '27Q', '27EQ'];

    public const STATUSES = [
        'pending' => 'Pending',
        'challan_paid' => 'Challan Paid',
        'in_progress' => 'In Progress',
        'filed' => 'Filed',
        'overdue' => 'Overdue',
    ];

    protected $fillable = [
        'client_id',
        'financial_year',
        'quarter',
        'form_type',
        'deduction_amount',
        'tax_deposited',
        'challan_number',
        'bsr_code',
        'challan_date',
        'due_date',
        'filing_date',
        'acknowledgement_number',
        'status',
        'assigned_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'deduction_amount' => 'decimal:2',
            'tax_deposited' => 'decimal:2',
            'challan_date' => 'date',
            'due_date' => 'date',
            'filing_date' => 'date',
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

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}

