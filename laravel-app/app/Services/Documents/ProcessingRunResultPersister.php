<?php

namespace App\Services\Documents;

use App\Enums\ProcessingRunStatus;
use App\Models\ProcessingRun;
use App\Services\Ai\Data\ProcessDocumentResult;
use LogicException;

class ProcessingRunResultPersister
{
    public function persist(
        ProcessingRun $processingRun,
        ProcessDocumentResult $result,
    ): void {
        if ((int) $processingRun->getKey() !== $result->processingRunId) {
            throw new LogicException(
                'Process document result does not belong to the processing run.',
            );
        }

        if ((int) $processingRun->document_id !== $result->documentId) {
            throw new LogicException(
                'Process document result does not belong to the processing run document.',
            );
        }

        if ($processingRun->profile !== $result->profile) {
            throw new LogicException(
                'Process document result profile does not match the processing run profile.',
            );
        }

        if ($processingRun->status !== ProcessingRunStatus::Processing) {
            throw new LogicException(
                'Only a processing run may persist a successful processing result.',
            );
        }

        if ($result->status !== ProcessingRunStatus::Indexed) {
            throw new LogicException(
                'Only an indexed processing result may be persisted as successful.',
            );
        }

        $processingRun->fill([
            'profile_snapshot' => $result->profileSnapshot,
            'total_pages' => $result->totalPages,
            'total_chunks' => $result->totalChunks,
            'vector_count' => $result->vectorCount,
            'vector_dimension' => $result->vectorDimension,
            'stage_timings_ms' => $result->stageTimingsMs,
            'warnings' => $result->warnings,
            'qdrant_collection' => $result->qdrantCollection,
            'status' => $result->status,
            'indexed_at' => now(),
        ]);

        $processingRun->save();
    }
}
