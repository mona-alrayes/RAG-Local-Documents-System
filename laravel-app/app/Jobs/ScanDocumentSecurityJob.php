<?php

namespace App\Jobs;

use App\Enums\DocumentSecurityScanStatus;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\Documents\DocumentSecurityService;
use App\Services\Documents\DocumentUploadService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ScanDocumentSecurityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
    ) {
        $this->onQueue(
            (string) config('security.clamav.queue', 'security-scan'),
        );
    }

    public function handle(
        DocumentSecurityService $securityService,
        DocumentUploadService $uploadService,
    ): void {
        $this->document->status = DocumentStatus::Scanning;
        $this->document->save();

        try {
            $filePath = Storage::disk('document_quarantine')
                ->path($this->document->file_path);

            $scanStatus = $securityService->scan($filePath);

            if ($scanStatus !== DocumentSecurityScanStatus::Clean) {
                $uploadService->rejectAfterUnsafeScan(
                    $this->document,
                    $scanStatus,
                );

                return;
            }

            $uploadService->promoteAfterCleanScan(
                $this->document,
                $scanStatus,
            );

            /*
         * AI processing has not been dispatched yet.
         * Keep the aggregate status pending until a real processing
         * job is dispatched, at which point it may become queued.
         */
            $this->document->status = DocumentStatus::Pending;
            $this->document->save();
        } catch (Throwable $exception) {
            $this->document->status = DocumentStatus::Failed;
            $this->document->save();

            throw $exception;
        }
    }
}
