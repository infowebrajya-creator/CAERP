<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_PARTNER = 'partner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_CLIENT = 'client';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_PARTNER => 'CA / Partner',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_EMPLOYEE => 'Accountant / Employee',
        self::ROLE_CLIENT => 'Client',
    ];

    public const STAFF_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_PARTNER,
        self::ROLE_MANAGER,
        self::ROLE_EMPLOYEE,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_code',
        'phone',
        'designation',
        'department',
        'is_active',
        'avatar_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role, $roles, true);
    }

    public function isStaff(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true);
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function isAdminOrPartner(): bool
    {
        return $this->hasRole([self::ROLE_SUPER_ADMIN, self::ROLE_PARTNER]);
    }

    public function isManagerOrAbove(): bool
    {
        return $this->hasRole([self::ROLE_SUPER_ADMIN, self::ROLE_PARTNER, self::ROLE_MANAGER]);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst(str_replace('_', ' ', $this->role));
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials ?: 'U';
    }

    public function clientProfile(): HasOne
    {
        return $this->hasOne(Client::class, 'user_id');
    }

    public function assignedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'assigned_ca_id');
    }

    public function secondaryClients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_staff')
            ->withPivot('role_label')
            ->withTimestamps();
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function gstReturns(): HasMany
    {
        return $this->hasMany(GstReturn::class, 'assigned_to');
    }

    public function itrFilings(): HasMany
    {
        return $this->hasMany(ItrFiling::class, 'assigned_to');
    }

    public function tdsReturns(): HasMany
    {
        return $this->hasMany(TdsReturn::class, 'assigned_to');
    }

    public function leadAudits(): HasMany
    {
        return $this->hasMany(Audit::class, 'lead_auditor_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('role', self::STAFF_ROLES);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
