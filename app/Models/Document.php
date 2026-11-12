<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    public const CATEGORIES = [
        'pan' => 'PAN',
        'aadhaar' => 'Aadhaar',
        'bank_statements' => 'Bank Statements',
        'invoices' => 'Invoices',
        'gst_documents' => 'GST Documents',
        'tds_certificates' => 'TDS Certificates',
        'audit_documents' => 'Audit Documents',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'requested' => 'Requested',
        'uploaded' => 'Uploaded',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'client_id',
        'title',
        'category',
        'status',
        'requested_by',
        'uploaded_by',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'request_notes',
        'due_date',
        'is_shared_with_client',
        'current_version',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'due_date' => 'date',
            'is_shared_with_client' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function formattedSize(): string
    {
        $bytes = (int) ($this->file_size ?? 0);
        if ($bytes <= 0) {
            return '—';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}

