<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunStatus;
use App\Exceptions\AiServiceException;
use App\Jobs\ProcessDocumentJob;
use App\Models\User;
use App\Services\Ai\AiServiceClient;
use App\Services\Ai\Data\ProcessDocumentRequestData;
use App\Services\Ai\Data\ProcessDocumentResult;
use App\Services\Documents\DocumentStorageService;
use App\Services\Documents\ProcessingRunActivator;
use App\Services\Documents\ProcessingRunResultPersister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_persists_successful_processing_result(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();

        $document = app(DocumentStorageService::class)->storePermanent(
            $user,
            UploadedFile::fake()->createWithContent(
                'trusted-notes.txt',
                "Trusted document content.\n",
            ),
        );

        $processingRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::HybridLocal,
            'status' => ProcessingRunStatus::Pending,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
        ]);

        $otherProcessingRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::Cloud,
            'status' => ProcessingRunStatus::Pending,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
        ]);

        $profileSnapshot = [
            'profile' => 'hybrid_local',
            'chunking' => [
                'chunk_size' => 800,
                'chunk_overlap' => 80,
            ],
        ];

        $stageTimingsMs = [
            'parse' => 100,
            'chunk' => 20,
            'dense_embedding' => 300,
            'sparse_representation' => 40,
            'total' => 460,
        ];

        $warnings = [
            [
                'code' => 'sample_warning',
                'message' => 'Sample processing warning.',
                'stage' => 'parse',
            ],
        ];

        $result = new ProcessDocumentResult(
            documentId: $document->id,
            processingRunId: $processingRun->id,
            profile: ProcessingProfile::HybridLocal,
            status: ProcessingRunStatus::Indexed,
            qdrantCollection: 'rag_documents_hybrid_local',
            profileSnapshot: $profileSnapshot,
            totalPages: null,
            totalChunks: 12,
            vectorCount: 12,
            vectorDimension: 1024,
            stageTimingsMs: $stageTimingsMs,
            warnings: $warnings,
        );

        $this->mock(
            AiServiceClient::class,
            function (MockInterface $mock) use (
                $document,
                $processingRun,
                $result,
                $user,
            ): void {
                $mock->shouldReceive('processDocument')
                    ->once()
                    ->withArgs(function (
                        ProcessDocumentRequestData $data,
                        string $filePath,
                        string $fileName,
                    ) use ($document, $processingRun, $user): bool {
                        $this->assertSame(
                            $user->id,
                            $data->userId,
                        );

                        $this->assertSame(
                            $document->id,
                            $data->documentId,
                        );

                        $this->assertSame(
                            $processingRun->id,
                            $data->processingRunId,
                        );

                        $this->assertSame(
                            ProcessingProfile::HybridLocal,
                            $data->processingProfile,
                        );

                        $this->assertSame(
                            $document->file_type,
                            $data->fileType,
                        );

                        $this->assertSame(
                            $document->file_path,
                            $filePath,
                        );

                        $this->assertSame(
                            $document->original_name,
                            $fileName,
                        );

                        return true;
                    })
                    ->andReturn($result);
            },
        );

        (new ProcessDocumentJob($processingRun->id))->handle(
            app(AiServiceClient::class),
            app(ProcessingRunResultPersister::class),
            app(ProcessingRunActivator::class),
        );

        $freshRun = $processingRun->fresh();

        $this->assertSame(
            ProcessingRunStatus::Indexed,
            $freshRun->status,
        );

        $this->assertSame(
            $profileSnapshot,
            $freshRun->profile_snapshot,
        );

        $this->assertNull(
            $freshRun->total_pages,
        );

        $this->assertSame(
            12,
            $freshRun->total_chunks,
        );

        $this->assertSame(
            12,
            $freshRun->vector_count,
        );

        $this->assertSame(
            1024,
            $freshRun->vector_dimension,
        );

        $this->assertSame(
            $stageTimingsMs,
            $freshRun->stage_timings_ms,
        );

        $this->assertSame(
            $warnings,
            $freshRun->warnings,
        );

        $this->assertSame(
            'rag_documents_hybrid_local',
            $freshRun->qdrant_collection,
        );

        $this->assertNotNull(
            $freshRun->indexed_at,
        );

        $this->assertSame(
            DocumentStatus::Processing,
            $document->fresh()->status,
        );

        $this->assertSame(
            $processingRun->id,
            $document->fresh()->active_processing_run_id,
        );

        $freshOtherRun = $otherProcessingRun->fresh();

        $this->assertSame(
            ProcessingRunStatus::Pending,
            $freshOtherRun->status,
        );

        $this->assertSame(
            [],
            $freshOtherRun->profile_snapshot,
        );

        $this->assertSame(
            0,
            $freshOtherRun->total_chunks,
        );

        $this->assertSame(
            0,
            $freshOtherRun->vector_count,
        );

        $this->assertNull(
            $freshOtherRun->qdrant_collection,
        );

        $this->assertNull(
            $freshOtherRun->indexed_at,
        );
    }

    public function test_client_failure_does_not_persist_successful_processing_result(): void
    {
        Storage::fake('documents');

        $document = app(DocumentStorageService::class)->storePermanent(
            User::factory()->create(),
            UploadedFile::fake()->createWithContent(
                'trusted-notes.txt',
                "Trusted document content.\n",
            ),
        );

        $processingRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::Cloud,
            'status' => ProcessingRunStatus::Pending,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
        ]);

        $this->mock(
            AiServiceClient::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('processDocument')
                    ->once()
                    ->andThrow(
                        new AiServiceException(
                            'AI service failed.',
                        ),
                    );
            },
        );

        try {
            (new ProcessDocumentJob($processingRun->id))->handle(
                app(AiServiceClient::class),
                app(ProcessingRunResultPersister::class),
                app(ProcessingRunActivator::class),
            );

            $this->fail('Expected AI service failure was not thrown.');
        } catch (AiServiceException $exception) {
            $this->assertSame(
                'AI service failed.',
                $exception->getMessage(),
            );
        }

        $freshRun = $processingRun->fresh();

        $this->assertSame(
            ProcessingRunStatus::Processing,
            $freshRun->status,
        );

        $this->assertSame(
            [],
            $freshRun->profile_snapshot,
        );

        $this->assertSame(
            0,
            $freshRun->total_chunks,
        );

        $this->assertSame(
            0,
            $freshRun->vector_count,
        );

        $this->assertNull(
            $freshRun->vector_dimension,
        );

        $this->assertSame(
            [],
            $freshRun->stage_timings_ms,
        );

        $this->assertNull(
            $freshRun->qdrant_collection,
        );

        $this->assertNull(
            $freshRun->indexed_at,
        );

        $this->assertNull(
            $document->fresh()->active_processing_run_id,
        );
    }

    public function test_successful_reprocessing_switches_active_run_before_old_run_cleanup(): void
    {
        Storage::fake('documents');

        $document = app(DocumentStorageService::class)->storePermanent(
            User::factory()->create(),
            UploadedFile::fake()->createWithContent(
                'trusted-notes.txt',
                "Trusted document content.\n",
            ),
        );

        $activeRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::Cloud,
            'status' => ProcessingRunStatus::Indexed,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
            'qdrant_collection' => 'rag_documents_cloud',
            'indexed_at' => now(),
        ]);

        $newRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::HybridLocal,
            'status' => ProcessingRunStatus::Pending,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
        ]);

        $document->active_processing_run_id = $activeRun->id;
        $document->status = DocumentStatus::Ready;
        $document->save();

        $result = new ProcessDocumentResult(
            documentId: $document->id,
            processingRunId: $newRun->id,
            profile: ProcessingProfile::HybridLocal,
            status: ProcessingRunStatus::Indexed,
            qdrantCollection: 'rag_documents_hybrid_local',
            profileSnapshot: [],
            totalPages: null,
            totalChunks: 2,
            vectorCount: 2,
            vectorDimension: 1024,
            stageTimingsMs: [],
            warnings: [],
        );

        $this->mock(
            AiServiceClient::class,
            function (MockInterface $mock) use (
                $activeRun,
                $document,
                $newRun,
                $result,
            ): void {
                $mock->shouldReceive('processDocument')
                    ->once()
                    ->withArgs(function () use (
                        $activeRun,
                        $document,
                    ): bool {
                        $freshDocument = $document->fresh();

                        $this->assertSame(
                            $activeRun->id,
                            $freshDocument->active_processing_run_id,
                        );

                        $this->assertSame(
                            DocumentStatus::Ready,
                            $freshDocument->status,
                        );

                        return true;
                    })
                    ->andReturn($result);

                $mock->shouldReceive('deleteProcessingRunPoints')
                    ->once()
                    ->withArgs(function (
                        int $userId,
                        int $documentId,
                        int $processingRunId,
                        ProcessingProfile $processingProfile,
                    ) use (
                        $activeRun,
                        $document,
                        $newRun,
                    ): bool {
                        $this->assertSame(
                            $newRun->id,
                            $document->fresh()->active_processing_run_id,
                        );

                        return $userId === (int) $document->user_id
                            && $documentId === (int) $document->id
                            && $processingRunId === (int) $activeRun->id
                            && $processingProfile === ProcessingProfile::Cloud;
                    });
            },
        );

        (new ProcessDocumentJob($newRun->id))->handle(
            app(AiServiceClient::class),
            app(ProcessingRunResultPersister::class),
            app(ProcessingRunActivator::class),
        );

        $this->assertSame(
            ProcessingRunStatus::Indexed,
            $newRun->fresh()->status,
        );

        $this->assertSame(
            $newRun->id,
            $document->fresh()->active_processing_run_id,
        );

        $this->assertSame(
            DocumentStatus::Ready,
            $document->fresh()->status,
        );
    }

    public function test_failed_reprocessing_keeps_old_run_active_without_cleanup(): void
    {
        Storage::fake('documents');

        $document = app(DocumentStorageService::class)->storePermanent(
            User::factory()->create(),
            UploadedFile::fake()->createWithContent(
                'trusted-notes.txt',
                "Trusted document content.\n",
            ),
        );

        $activeRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::Cloud,
            'status' => ProcessingRunStatus::Indexed,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
            'qdrant_collection' => 'rag_documents_cloud',
            'indexed_at' => now(),
        ]);

        $newRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::HybridLocal,
            'status' => ProcessingRunStatus::Pending,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
        ]);

        $document->active_processing_run_id = $activeRun->id;
        $document->status = DocumentStatus::Ready;
        $document->save();

        $this->mock(
            AiServiceClient::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('processDocument')
                    ->once()
                    ->andThrow(new AiServiceException('AI service failed.'));

                $mock->shouldNotReceive('deleteProcessingRunPoints');
            },
        );

        try {
            (new ProcessDocumentJob($newRun->id))->handle(
                app(AiServiceClient::class),
                app(ProcessingRunResultPersister::class),
                app(ProcessingRunActivator::class),
            );

            $this->fail('Expected AI service failure was not thrown.');
        } catch (AiServiceException $exception) {
            $this->assertSame(
                'AI service failed.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(
            $activeRun->id,
            $document->fresh()->active_processing_run_id,
        );

        $this->assertSame(
            DocumentStatus::Ready,
            $document->fresh()->status,
        );

        $this->assertSame(
            ProcessingRunStatus::Processing,
            $newRun->fresh()->status,
        );
    }
}
