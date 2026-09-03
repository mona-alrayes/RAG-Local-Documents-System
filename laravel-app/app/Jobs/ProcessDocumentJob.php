<?php

namespace App\Jobs;

use App\Enums\ProcessingRunStatus;
use App\Models\Document;
use App\Models\ProcessingRun;
use App\Services\Ai\AiServiceClient;
use App\Services\Ai\Data\ProcessDocumentRequestData;
use App\Services\Documents\DocumentStatusProjector;
use App\Services\Documents\ProcessingRunActivator;
use App\Services\Documents\ProcessingRunResultPersister;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $processingRunId,
    ) {}

    public function handle(
        AiServiceClient $client,
        ProcessingRunResultPersister $resultPersister,
        ProcessingRunActivator $processingRunActivator,
        DocumentStatusProjector $documentStatusProjector,
    ): void {
        $processingRun = ProcessingRun::query()
            ->with('document')
            ->findOrFail($this->processingRunId);

        $document = $processingRun->document;

        if (! $document instanceof Document) {
            throw new \RuntimeException(
                'Processing run does not have an associated document.',
            );
        }

        $processingRun->status = ProcessingRunStatus::Processing;
        $processingRun->save();

        $documentStatusProjector->project($document, $processingRun);

        $requestData = new ProcessDocumentRequestData(
            userId: (int) $document->user_id,
            documentId: (int) $document->id,
            processingRunId: (int) $processingRun->id,
            processingProfile: $processingRun->profile,
            fileType: $document->file_type,
        );

        $result = $client->processDocument(
            data: $requestData,
            filePath: $document->file_path,
            fileName: $document->original_name,
        );

        $resultPersister->persist(
            processingRun: $processingRun,
            result: $result,
        );

        $previousProcessingRun = $processingRunActivator->activate(
            $processingRun,
        );

        if ($previousProcessingRun instanceof ProcessingRun) {
            $client->deleteProcessingRunPoints(
                userId: (int) $document->user_id,
                documentId: (int) $document->getKey(),
                processingRunId: (int) $previousProcessingRun->getKey(),
                processingProfile: $previousProcessingRun->profile,
            );
        }
    }
}
