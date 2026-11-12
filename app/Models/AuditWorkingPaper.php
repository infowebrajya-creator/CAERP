<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditWorkingPaper extends Model
{
    protected $fillable = [
        'audit_id',
        'title',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_by',
        'notes',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

