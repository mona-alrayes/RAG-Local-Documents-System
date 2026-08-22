<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;

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
}
