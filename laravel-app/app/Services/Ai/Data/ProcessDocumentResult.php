<?php

namespace App\Services\Ai\Data;

use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunStatus;

final readonly class ProcessDocumentResult
{
    /**
     * @param  array<string, mixed>  $profileSnapshot
     * @param  array<string, int>  $stageTimingsMs
     * @param  list<array<string, mixed>>  $warnings
     */
    public function __construct(
        public int $documentId,
        public int $processingRunId,
        public ProcessingProfile $profile,
        public ProcessingRunStatus $status,
        public string $qdrantCollection,
        public array $profileSnapshot,
        public ?int $totalPages,
        public int $totalChunks,
        public int $vectorCount,
        public ?int $vectorDimension,
        public array $stageTimingsMs,
        public array $warnings,
    ) {}
}
