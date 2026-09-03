<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunKind;
use App\Enums\ProcessingRunStatus;
use App\Exceptions\AiServiceException;
use App\Models\Document;
use App\Models\ProcessingRun;
use App\Models\User;
use App\Services\Ai\AiServiceClient;
use App\Services\Documents\DocumentStorageService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentDeleteCommandTest extends TestCase
{
    use DatabaseMigrations;

    public function test_owner_can_delete_document_and_all_external_data(): void
    {
        Storage::fake('documents');
        Storage::fake('document_quarantine');

        $user = User::factory()->create();

        [$document, $oldRun, $activeRun] = $this->readyDocument($user);

        $aiServiceClient = Mockery::mock(AiServiceClient::class);

        $aiServiceClient
            ->shouldReceive('deleteProcessingRunPoints')
            ->once()
            ->ordered()
            ->with(
                (int) $user->id,
                (int) $document->id,
                (int) $oldRun->id,
                ProcessingProfile::Cloud,
            );

        $aiServiceClient
            ->shouldReceive('deleteProcessingRunPoints')
            ->once()
            ->ordered()
            ->with(
                (int) $user->id,
                (int) $document->id,
                (int) $activeRun->id,
                ProcessingProfile::HybridLocal,
            );

        $this->app->instance(
            AiServiceClient::class,
            $aiServiceClient,
        );

        $this->actingAs($user)
            ->delete(route('documents.destroy', $document))
            ->assertRedirectToRoute('documents.index')
            ->assertSessionHas(
                'success',
                __('documents.commands.delete.success'),
            );

        Storage::disk('documents')
            ->assertMissing($document->file_path);

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);

        $this->assertDatabaseMissing('document_processing_runs', [
            'document_id' => $document->id,
        ]);
    }

    public function test_non_owner_cannot_delete_document(): void
    {
        Storage::fake('documents');
        Storage::fake('document_quarantine');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        [$document] = $this->readyDocument($owner);

        $aiServiceClient = Mockery::mock(AiServiceClient::class);

        $aiServiceClient
            ->shouldNotReceive('deleteProcessingRunPoints');

        $this->app->instance(
            AiServiceClient::class,
            $aiServiceClient,
        );

        $this->actingAs($otherUser)
            ->delete(route('documents.destroy', $document))
            ->assertForbidden();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
        ]);

        Storage::disk('documents')
            ->assertExists($document->file_path);
    }

    public function test_document_cannot_be_deleted_while_processing_is_in_progress(): void
    {
        Storage::fake('documents');
        Storage::fake('document_quarantine');

        $user = User::factory()->create();

        [$document] = $this->readyDocument($user);

        $document->processingRuns()->create([
            'profile' => ProcessingProfile::Cloud,
            'status' => ProcessingRunStatus::Pending,
            'kind' => ProcessingRunKind::Reprocessing,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
        ]);

        $aiServiceClient = Mockery::mock(AiServiceClient::class);

        $aiServiceClient
            ->shouldNotReceive('deleteProcessingRunPoints');

        $this->app->instance(
            AiServiceClient::class,
            $aiServiceClient,
        );

        $this->actingAs($user)
            ->delete(route('documents.destroy', $document))
            ->assertRedirectToRoute('documents.show', $document)
            ->assertSessionHas(
                'error',
                __('documents.commands.delete.processing_in_progress'),
            );

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
        ]);

        Storage::disk('documents')
            ->assertExists($document->file_path);

        $this->assertDatabaseCount(
            'document_processing_runs',
            3,
        );
    }

    public function test_failed_qdrant_cleanup_preserves_local_document_data(): void
    {
        Storage::fake('documents');
        Storage::fake('document_quarantine');

        $user = User::factory()->create();

        [$document, $oldRun] = $this->readyDocument($user);

        $aiServiceClient = Mockery::mock(AiServiceClient::class);

        $aiServiceClient
            ->shouldReceive('deleteProcessingRunPoints')
            ->once()
            ->with(
                (int) $user->id,
                (int) $document->id,
                (int) $oldRun->id,
                ProcessingProfile::Cloud,
            )
            ->andThrow(
                new AiServiceException(
                    message: 'Qdrant cleanup failed.',
                ),
            );

        $this->app->instance(
            AiServiceClient::class,
            $aiServiceClient,
        );

        $this->actingAs($user)
            ->delete(route('documents.destroy', $document))
            ->assertRedirectToRoute('documents.show', $document)
            ->assertSessionHas(
                'error',
                __('documents.commands.delete.cleanup_failed'),
            );

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
        ]);

        $this->assertDatabaseCount(
            'document_processing_runs',
            2,
        );

        Storage::disk('documents')
            ->assertExists($document->file_path);
    }

    public function test_quarantined_document_can_be_deleted_without_processing_runs(): void
    {
        Storage::fake('documents');
        Storage::fake('document_quarantine');

        $user = User::factory()->create();

        $document = app(DocumentStorageService::class)
            ->storeQuarantined(
                $user,
                UploadedFile::fake()->createWithContent(
                    'unsafe.txt',
                    "Quarantined document content.\n",
                ),
            );

        $aiServiceClient = Mockery::mock(AiServiceClient::class);

        $aiServiceClient
            ->shouldNotReceive('deleteProcessingRunPoints');

        $this->app->instance(
            AiServiceClient::class,
            $aiServiceClient,
        );

        $this->actingAs($user)
            ->delete(route('documents.destroy', $document))
            ->assertRedirectToRoute('documents.index')
            ->assertSessionHas(
                'success',
                __('documents.commands.delete.success'),
            );

        Storage::disk('document_quarantine')
            ->assertMissing($document->file_path);

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
    }

    /**
     * @return array{Document, ProcessingRun, ProcessingRun}
     */
    private function readyDocument(User $user): array
    {
        $document = app(DocumentStorageService::class)
            ->storePermanent(
                $user,
                UploadedFile::fake()->createWithContent(
                    'ready-document.txt',
                    "Ready document content.\n",
                ),
            );

        $oldRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::Cloud,
            'status' => ProcessingRunStatus::Failed,
            'kind' => ProcessingRunKind::Initial,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
        ]);

        $activeRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::HybridLocal,
            'status' => ProcessingRunStatus::Indexed,
            'kind' => ProcessingRunKind::Reprocessing,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
        ]);

        $document->forceFill([
            'active_processing_run_id' => $activeRun->id,
            'status' => DocumentStatus::Ready,
        ])->save();

        return [
            $document->fresh(),
            $oldRun,
            $activeRun,
        ];
    }
}
