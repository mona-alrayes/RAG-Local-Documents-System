<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\ProcessingRun;
use App\Services\Ai\AiServiceClient;
use App\Services\Ai\Data\ProcessDocumentRequestData;
use App\Services\Documents\ProcessingRunActivator;
use App\Services\Documents\ProcessingRunProgressor;
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
        ProcessingRunProgressor $processingRunProgressor,
        ProcessingRunResultPersister $resultPersister,
        ProcessingRunActivator $processingRunActivator,
    ): void {
        $processingRun = $processingRunProgressor
            ->markProcessingStarted($this->processingRunId)
            ->load('document');

        $document = $processingRun->document;

        if (! $document instanceof Document) {
            throw new \RuntimeException(
                'Processing run does not have an associated document.',
            );
        }

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

        $indexedProcessingRun = $resultPersister->persist(
            processingRunId: $this->processingRunId,
            result: $result,
        );

        $previousProcessingRun = $processingRunActivator->activate(
            $indexedProcessingRun,
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
