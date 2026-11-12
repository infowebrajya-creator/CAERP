<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GstReturnType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'frequency',
        'default_due_day',
        'default_due_month_offset',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function returns(): HasMany
    {
        return $this->hasMany(GstReturn::class, 'return_type_id');
    }
}

