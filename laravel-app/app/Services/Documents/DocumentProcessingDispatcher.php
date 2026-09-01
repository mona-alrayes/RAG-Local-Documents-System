<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\ProcessingRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;

class DocumentProcessingDispatcher
{
    public function dispatchInitial(
        Document $document,
        ProcessingProfile $profile,
    ): ProcessingRun {
        if (! Storage::disk('documents')->exists($document->file_path)) {
            throw new RuntimeException(
                'Document must exist in permanent private storage before processing.',
            );
        }

        return DB::transaction(function () use ($document, $profile): ProcessingRun {
            $lockedDocument = Document::query()
                ->lockForUpdate()
                ->findOrFail($document->id);

            if ($lockedDocument->processingRuns()->exists()) {
                throw new LogicException(
                    'Initial document processing has already been dispatched.',
                );
            }

            $processingRun = $lockedDocument->processingRuns()->create([
                'profile' => $profile,
                'status' => ProcessingRunStatus::Pending,
                'profile_snapshot' => [],
                'stage_timings_ms' => [],
            ]);

            $lockedDocument->status = DocumentStatus::Queued;
            $lockedDocument->save();

            ProcessDocumentJob::dispatch($processingRun->id)->afterCommit();

            return $processingRun;
        });
    }
}
