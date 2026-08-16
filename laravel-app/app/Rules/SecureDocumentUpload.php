<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class SecureDocumentUpload implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute must be a valid uploaded file.');

            return;
        }

        $originalName = $value->getClientOriginalName();

        $originalPath = method_exists($value, 'getClientOriginalPath')
            ? $value->getClientOriginalPath()
            : $originalName;

        if (
            $originalPath !== $originalName
            || ! $this->hasSafeOriginalName($originalName)
        ) {
            $fail('The :attribute filename is unsafe.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $allowedTypes = config('documents.upload.types', []);
        $allowedMimeTypes = $allowedTypes[$extension] ?? null;

        if (! is_array($allowedMimeTypes)) {
            $fail('The :attribute type is not supported.');

            return;
        }

        $mimeType = $value->getMimeType();

        if (
            ! is_string($mimeType)
            || ! in_array($mimeType, $allowedMimeTypes, true)
        ) {
            $fail('The :attribute content does not match its extension.');

            return;
        }

        $path = $value->getRealPath();

        if (! is_string($path)) {
            $fail('The :attribute could not be inspected.');

            return;
        }

        $isValid = match ($extension) {
            'pdf' => $this->isValidPdf($path),
            'docx' => $this->isValidDocx($path),
            'txt' => $this->isValidText($path),
            default => false,
        };

        if (! $isValid) {
            $fail('The :attribute is malformed or unsafe.');
        }
    }

    private function hasSafeOriginalName(string $originalName): bool
    {
        $maxLength = (int) config(
            'documents.upload.max_original_name_length',
            255,
        );

        if (
            $originalName === ''
            || ! mb_check_encoding($originalName, 'UTF-8')
            || mb_strlen($originalName, 'UTF-8') > $maxLength
        ) {
            return false;
        }

        $configuredTypes = config('documents.upload.types', []);

        if (! is_array($configuredTypes)) {
            return false;
        }

        $extensions = array_filter(
            array_keys($configuredTypes),
            static fn (mixed $extension): bool => is_string($extension),
        );

        if ($extensions === []) {
            return false;
        }

        $extensionPattern = implode(
            '|',
            array_map(
                static fn (string $extension): string => preg_quote(
                    $extension,
                    '/',
                ),
                $extensions,
            ),
        );

        $pattern = '/\A'
            .'[\p{L}\p{N}]'
            .'(?:[\p{L}\p{N} _-]*[\p{L}\p{N}_-])?'
            .'\.(?:'.$extensionPattern.')'
            .'\z/uiD';

        return preg_match($pattern, $originalName) === 1;
    }

    private function isValidPdf(string $path): bool
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return false;
        }

        return str_starts_with($content, '%PDF-')
            && str_contains($content, '%%EOF');
    }

    private function isValidDocx(string $path): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return false;
        }

        try {
            $maxEntries = (int) config(
                'documents.upload.docx.max_entries',
                1000,
            );

            $maxUncompressedBytes = (int) config(
                'documents.upload.docx.max_uncompressed_bytes',
                50 * 1024 * 1024,
            );

            if ($zip->numFiles > $maxEntries) {
                return false;
            }

            $requiredEntries = [
                '[Content_Types].xml',
                '_rels/.rels',
                'word/document.xml',
            ];

            foreach ($requiredEntries as $requiredEntry) {
                if ($zip->locateName($requiredEntry) === false) {
                    return false;
                }
            }

            $totalUncompressedBytes = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);

                if ($entry === false) {
                    return false;
                }

                $entryName = $entry['name'] ?? null;
                $entrySize = $entry['size'] ?? null;

                if (
                    ! is_string($entryName)
                    || ! is_int($entrySize)
                    || $this->hasUnsafeArchivePath($entryName)
                ) {
                    return false;
                }

                $totalUncompressedBytes += $entrySize;

                if ($totalUncompressedBytes > $maxUncompressedBytes) {
                    return false;
                }
            }

            return true;
        } finally {
            $zip->close();
        }
    }

    private function hasUnsafeArchivePath(string $entryName): bool
    {
        return str_starts_with($entryName, '/')
            || str_starts_with($entryName, '\\')
            || preg_match('/^[a-zA-Z]:[\\\\\/]/', $entryName) === 1
            || in_array(
                '..',
                preg_split('/[\\\\\/]+/', $entryName),
                true,
            );
    }

    private function isValidText(string $path): bool
    {
        $content = file_get_contents($path);

        if (
            $content === false
            || str_contains($content, "\0")
            || ! mb_check_encoding($content, 'UTF-8')
        ) {
            return false;
        }

        $prefix = substr($content, 0, 1024);

        if (str_contains($prefix, '%PDF-')) {
            return false;
        }

        $zipSignatures = [
            "PK\x03\x04",
            "PK\x05\x06",
            "PK\x07\x08",
        ];

        foreach ($zipSignatures as $signature) {
            if (str_starts_with($content, $signature)) {
                return false;
            }
        }

        return true;
    }
}
