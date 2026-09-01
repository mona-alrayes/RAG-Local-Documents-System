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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_builds_server_side_request_data_and_calls_ai_client_once(): void
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

        $result = new ProcessDocumentResult(
            documentId: $document->id,
            processingRunId: $processingRun->id,
            profile: ProcessingProfile::HybridLocal,
            status: ProcessingRunStatus::Indexed,
            qdrantCollection: 'rag_documents_hybrid_local',
            profileSnapshot: ['profile' => 'hybrid_local'],
            totalPages: null,
            totalChunks: 1,
            vectorCount: 1,
            vectorDimension: 1024,
            stageTimingsMs: ['total' => 10],
            warnings: [],
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
                        $this->assertSame($user->id, $data->userId);
                        $this->assertSame($document->id, $data->documentId);
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
                        $this->assertSame($document->file_path, $filePath);
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
        );

        $this->assertSame(
            ProcessingRunStatus::Processing,
            $processingRun->fresh()->status,
        );
        $this->assertSame(
            DocumentStatus::Processing,
            $document->fresh()->status,
        );
    }

    public function test_job_does_not_swallow_ai_service_failure(): void
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
                    ->andThrow(new AiServiceException('AI service failed.'));
            },
        );

        $this->expectException(AiServiceException::class);
        $this->expectExceptionMessage('AI service failed.');

        (new ProcessDocumentJob($processingRun->id))->handle(
            app(AiServiceClient::class),
        );
    }
}
