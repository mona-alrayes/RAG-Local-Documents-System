<?php

namespace App\Services\Documents;

use App\Enums\FileType;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DocumentStorageService
{
    public function store(User $user, UploadedFile $file): Document
    {
        $fileType = FileType::from(
            strtolower($file->getClientOriginalExtension()),
        );

        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        if (! is_string($mimeType) || ! is_int($fileSize)) {
            throw new RuntimeException(
                'Unable to determine trusted document metadata.',
            );
        }

        $disk = Storage::disk('documents');

        do {
            $storedName = Str::ulid().'.'.$fileType->value;
            $filePath = $user->id.'/'.$storedName;
        } while ($disk->exists($filePath));

        $storedPath = $disk->putFileAs(
            (string) $user->id,
            $file,
            $storedName,
        );

        if (! is_string($storedPath)) {
            throw new RuntimeException('Unable to store document.');
        }

        try {
            return $user->documents()->create([
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'file_path' => $storedPath,
                'file_type' => $fileType,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]);
        } catch (Throwable $exception) {
            $disk->delete($storedPath);

            throw $exception;
        }
    }
}
