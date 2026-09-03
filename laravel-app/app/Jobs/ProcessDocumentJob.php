<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\ProcessingRun;
use App\Services\Ai\AiServiceClient;
use App\Services\Ai\Data\ProcessDocumentRequestData;
use App\Services\Documents\ProcessingRunActivator;
use App\Services\Documents\ProcessingRunFailureClassifier;
use App\Services\Documents\ProcessingRunFailureFinalizer;
use App\Services\Documents\ProcessingRunProgressor;
use App\Services\Documents\ProcessingRunResultPersister;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\TimeoutExceededException;
use Throwable;

#[Tries(3)] // الحد الأقصى لعدد محاولات تنفيذ الـ Job هو 3 محاولات
#[Backoff([15, 60])] // الانتظار 15 ثانية قبل المحاولة الثانية و60 ثانية قبل المحاولة الثالثة
#[Timeout(330)] // إنهاء محاولة الـ Job إذا تجاوز تنفيذها 330 ثانية
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
        ProcessingRunFailureClassifier $failureClassifier,
        ProcessingRunFailureFinalizer $failureFinalizer,
    ): void {
        try {
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
        } catch (Throwable $exception) {
            if ($failureClassifier->isRetryable($exception)) {
                // الخطأ المؤقت يُعاد رميه حتى تعيد Laravel نفس الـ Job ونفس الـ Run.
                throw $exception;
            }

            // الخطأ الدائم يُسجّل فوراً كفشل نهائي.
            $failureFinalizer->finalize(
                processingRunId: $this->processingRunId,
                errorCode: $failureClassifier->terminalErrorCode($exception),
                failureReason: $failureClassifier->terminalFailureReason(
                    $exception,
                ),
            );

            // إيقاف الـ Job نهائياً وعدم استهلاك باقي محاولات الـ retry.
            $this->fail($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $timedOut = $exception instanceof TimeoutExceededException;

        // عند انتهاء جميع محاولات الـ retry أو حدوث timeout نهائي،
        // نغلق الـ ProcessingRun كفشل نهائي بشكل آمن وIdempotent.
        app(ProcessingRunFailureFinalizer::class)->finalize(
            processingRunId: $this->processingRunId,
            errorCode: $timedOut
                ? 'processing_timeout_exhausted'
                : 'processing_retries_exhausted',
            failureReason: $timedOut
                ? 'Document processing timed out after all allowed attempts.'
                : 'Document processing failed after all allowed retry attempts.',
        );
    }
}
