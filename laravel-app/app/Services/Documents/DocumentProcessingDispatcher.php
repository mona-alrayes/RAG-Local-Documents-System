<?php

namespace App\Services\Documents;

use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunKind;
use App\Enums\ProcessingRunStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Models\ProcessingRun;
use App\Services\Ai\ProcessingCapabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;

/**
 * Dispatches initial processing and reprocessing attempts.
 *
 * قبل إنشاء أي ProcessingRun يتم التأكد أن الـprocessing profile
 * متاح حاليًا لدى AI service. إذا فشل capability lookup أو كان
 * الـprofile غير متاح، يتم الرفض بدون تعديل حالة الوثيقة أو إنشاء run جديدة.
 */
class DocumentProcessingDispatcher
{
    public function __construct(
        private readonly DocumentStatusProjector $documentStatusProjector,
        private readonly ProcessingCapabilityService $processingCapabilityService,
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

        $this->processingCapabilityService->assertAvailable($profile);

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
                'kind' => ProcessingRunKind::Initial,
                'profile_snapshot' => [],
                'stage_timings_ms' => [],
            ]);

            $this->documentStatusProjector->project(
                $lockedDocument,
                $processingRun,
            );

            $this->dispatchProcessingJob($processingRun);

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

        $this->processingCapabilityService->assertAvailable($profile);

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
                'kind' => ProcessingRunKind::Reprocessing,
                'profile_snapshot' => [],
                'stage_timings_ms' => [],
            ]);

            $this->dispatchProcessingJob($processingRun);

            return $processingRun;
        });
    }

    private function dispatchProcessingJob(
        ProcessingRun $processingRun,
    ): void {
        ProcessDocumentJob::dispatch($processingRun->id)
            ->onQueue(
                $this->queueForProfile($processingRun->profile),
            )
            ->afterCommit();
    }

    private function queueForProfile(
        ProcessingProfile $profile,
    ): string {
        return match ($profile) {
            ProcessingProfile::Cloud => (string) config(
                'queue.processing.cloud_queue',
                'default',
            ),

            ProcessingProfile::HybridLocal => (string) config(
                'queue.processing.local_queue',
                'ai-local',
            ),
        };
    }
}
