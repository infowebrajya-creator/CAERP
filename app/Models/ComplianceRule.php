<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceRule extends Model
{
    protected $fillable = [
        'name',
        'compliance_type',
        'frequency',
        'due_day',
        'due_month',
        'month_offset',
        'reminder_days_before',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(ComplianceDeadline::class);
    }
}

