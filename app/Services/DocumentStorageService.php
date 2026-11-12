<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manages secure private file storage, document versioning, and review workflows.
 */
class DocumentStorageService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function storeUpload(Document $document, UploadedFile $file, int $uploadedBy, ?string $notes = null): Document
    {
        return DB::transaction(function () use ($document, $file, $uploadedBy, $notes) {
            $nextVersion = ((int) $document->current_version) + 1;
            $directory = "documents/client_{$document->client_id}";
            $storedPath = $file->store($directory, 'local');

            DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => $nextVersion,
                'file_path' => $storedPath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $uploadedBy,
                'notes' => $notes,
            ]);

            $document->update([
                'status' => 'uploaded',
                'uploaded_by' => $uploadedBy,
                'current_version' => $nextVersion,
                'file_path' => $storedPath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'rejection_reason' => null,
            ]);

            $this->logger->log(
                'documents',
                'uploaded',
                "Uploaded version v{$nextVersion} for document '{$document->title}'",
                $document
            );

            return $document->fresh(['versions', 'client']);
        });
    }

    public function download(Document $document, ?DocumentVersion $version = null): StreamedResponse
    {
        $path = $version?->file_path ?? $document->file_path;
        $filename = $version?->original_filename ?? $document->original_filename ?? 'document';

        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Stored file not found.');

        return Storage::disk('local')->download($path, $filename);
    }

    public function approve(Document $document, int $reviewerId): Document
    {
        $document->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->logger->log('documents', 'approved', "Approved document '{$document->title}'", $document);

        return $document;
    }

    public function reject(Document $document, int $reviewerId, string $reason): Document
    {
        $document->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->logger->log('documents', 'rejected', "Rejected document '{$document->title}': {$reason}", $document);

        return $document;
    }
}

