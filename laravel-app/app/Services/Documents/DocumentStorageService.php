<?php

namespace App\Services\Documents;

use App\Enums\FileType;
use App\Exceptions\DuplicateDocumentException;
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
            $stream = $disk->readStream($storedPath);

            if (! is_resource($stream)) {
                throw new RuntimeException(
                    'Unable to read stored document.',
                );
            }

            try {
                $hashContext = hash_init('sha256');
                hash_update_stream($hashContext, $stream);
                $sha256 = hash_final($hashContext);
            } finally {
                fclose($stream);
            }

            $duplicate = $user->documents()
                ->where('sha256', $sha256)
                ->first();

            if ($duplicate instanceof Document) {
                throw new DuplicateDocumentException($duplicate);
            }

            return $user->documents()->create([
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'file_path' => $storedPath,
                'file_type' => $fileType,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'sha256' => $sha256,
            ]);
        } catch (Throwable $exception) {
            $disk->delete($storedPath);

            throw $exception;
        }
    }
}
