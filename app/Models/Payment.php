<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const MODES = [
        'bank_transfer' => 'Bank Transfer (NEFT/RTGS/IMPS)',
        'upi' => 'UPI',
        'cheque' => 'Cheque',
        'cash' => 'Cash',
        'card' => 'Card',
    ];

    protected $fillable = [
        'invoice_id',
        'client_id',
        'payment_date',
        'amount',
        'payment_mode',
        'reference_number',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

