<?php

namespace App\Services\Documents;

use App\Enums\ProcessingRunStatus;
use App\Models\Document;
use App\Models\ProcessingRun;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProcessingRunActivator
{
    public function activate(ProcessingRun $processingRun): void
    {
        $processingRunId = (int) $processingRun->getKey();
        $documentId = (int) $processingRun->document_id;

        DB::transaction(function () use ($processingRunId, $documentId): void {
            $document = Document::query()
                ->lockForUpdate()
                ->findOrFail($documentId);

            $lockedProcessingRun = ProcessingRun::query()
                ->lockForUpdate()
                ->findOrFail($processingRunId);

            if ((int) $lockedProcessingRun->document_id !== (int) $document->getKey()) {
                throw new LogicException(
                    'Processing run does not belong to the document being activated.',
                );
            }

            if ($lockedProcessingRun->status !== ProcessingRunStatus::Indexed) {
                throw new LogicException(
                    'Only an indexed processing run may be activated.',
                );
            }

            if ($document->active_processing_run_id !== null) {
                throw new LogicException(
                    'Document already has an active processing run.',
                );
            }

            $document->active_processing_run_id = $lockedProcessingRun->getKey();
            $document->save();
        });
    }
}
