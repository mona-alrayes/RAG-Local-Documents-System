<?php

namespace App\Services\Documents;

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
    public function __construct(
        private readonly DocumentStatusProjector $documentStatusProjector,
    ) {}

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

            $this->documentStatusProjector->project(
                $lockedDocument,
                $processingRun,
            );

            ProcessDocumentJob::dispatch($processingRun->id)->afterCommit();

            return $processingRun;
        });
    }

    public function dispatchReprocessing(
        Document $document,
        ProcessingProfile $profile,
    ): ProcessingRun {
        if (! Storage::disk('documents')->exists($document->file_path)) {
            throw new RuntimeException(
                'Document must exist in permanent private storage before reprocessing.',
            );
        }

        return DB::transaction(function () use ($document, $profile): ProcessingRun {
            $lockedDocument = Document::query()
                ->lockForUpdate()
                ->findOrFail($document->id);

            if ($lockedDocument->active_processing_run_id === null) {
                throw new LogicException(
                    'Document must have an active processing run before reprocessing.',
                );
            }

            $activeProcessingRun = ProcessingRun::query()
                ->lockForUpdate()
                ->find($lockedDocument->active_processing_run_id);

            if (
                ! $activeProcessingRun instanceof ProcessingRun
                || (int) $activeProcessingRun->document_id
                !== (int) $lockedDocument->getKey()
                || $activeProcessingRun->status
                !== ProcessingRunStatus::Indexed
            ) {
                throw new LogicException(
                    'Document active processing run is invalid for reprocessing.',
                );
            }

            $hasProcessingRunInProgress = $lockedDocument
                ->processingRuns()
                ->whereIn('status', [
                    ProcessingRunStatus::Pending->value,
                    ProcessingRunStatus::Processing->value,
                    ProcessingRunStatus::Indexing->value,
                ])
                ->exists();

            if ($hasProcessingRunInProgress) {
                throw new LogicException(
                    'Document reprocessing is already in progress.',
                );
            }

            $processingRun = $lockedDocument->processingRuns()->create([
                'profile' => $profile,
                'status' => ProcessingRunStatus::Pending,
                'profile_snapshot' => [],
                'stage_timings_ms' => [],
            ]);

            ProcessDocumentJob::dispatch($processingRun->id)->afterCommit();

            return $processingRun;
        });
    }
}
