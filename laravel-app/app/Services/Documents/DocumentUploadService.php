<?php

namespace App\Services\Documents;

use App\Enums\DocumentSecurityScanStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use LogicException;

class DocumentUploadService
{
    public function __construct(
        private readonly DocumentStorageService $storage,
    ) {}

    public function store(
        User $user,
        UploadedFile $file,
    ): Document {
        return $this->storage->storeQuarantined(
            $user,
            $file,
        );
    }

    public function promoteAfterCleanScan(
        Document $document,
        DocumentSecurityScanStatus $scanStatus,
    ): void {
        if ($scanStatus !== DocumentSecurityScanStatus::Clean) {
            throw new LogicException(
                'Document cannot be promoted without a clean security scan.',
            );
        }

        $this->storage->promoteQuarantined($document);
    }
}
