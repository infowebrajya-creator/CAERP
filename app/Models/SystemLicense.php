<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_hash',
        'license_key',
        'licensed_to',
        'email',
        'phone',
        'machine_fingerprint',
        'status',
        'edition',
        'max_staff_users',
        'activated_at',
        'amc_expires_at',
        'last_verified_at',
        'offline_token',
        'meta_data',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'amc_expires_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'meta_data' => 'array',
        'max_staff_users' => 'integer',
    ];

    /**
     * Check if the license is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if AMC maintenance has expired.
     */
    public function isAmcExpired(): bool
    {
        if (! $this->amc_expires_at) {
            return false;
        }

        return $this->amc_expires_at->isPast();
    }

    /**
     * Calculate remaining AMC days.
     */
    public function remainingAmcDays(): int
    {
        if (! $this->amc_expires_at) {
            return 9999;
        }

        return max(0, (int) now()->diffInDays($this->amc_expires_at, false));
    }

    /**
     * Scope query to search by term.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('licensed_to', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('machine_fingerprint', 'like', "%{$term}%")
              ->orWhere('license_key', 'like', "%{$term}%");
        });
    }

    /**
     * Get sale price or default ₹45,000.
     */
    public function getSalePrice(): float
    {
        return (float) ($this->meta_data['sale_price'] ?? 45000.00);
    }
}
