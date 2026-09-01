<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\User;
use App\Services\Documents\DocumentProcessingDispatcher;
use App\Services\Documents\DocumentStorageService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class DocumentProcessingDispatcherTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_creates_one_run_and_dispatches_processing_after_permanent_storage(): void
    {
        Queue::fake();
        Storage::fake('documents');

        $document = app(DocumentStorageService::class)->storePermanent(
            User::factory()->create(),
            UploadedFile::fake()->createWithContent(
                'notes.txt',
                "Permanent document content.\n",
            ),
        );

        $processingRun = app(DocumentProcessingDispatcher::class)
            ->dispatchInitial($document, ProcessingProfile::HybridLocal);

        $this->assertSame(
            DocumentStatus::Queued,
            $document->fresh()->status,
        );
        $this->assertSame(
            ProcessingProfile::HybridLocal,
            $processingRun->profile,
        );
        $this->assertSame(
            ProcessingRunStatus::Pending,
            $processingRun->status,
        );
        $this->assertSame([], $processingRun->profile_snapshot);
        $this->assertSame([], $processingRun->stage_timings_ms);
        $this->assertDatabaseCount('document_processing_runs', 1);

        Queue::assertPushed(
            ProcessDocumentJob::class,
            fn (ProcessDocumentJob $job) => $job->processingRunId
                === $processingRun->id,
        );
    }

    public function test_it_does_not_create_a_second_initial_run(): void
    {
        Queue::fake();
        Storage::fake('documents');

        $document = app(DocumentStorageService::class)->storePermanent(
            User::factory()->create(),
            UploadedFile::fake()->createWithContent(
                'notes.txt',
                "Permanent document content.\n",
            ),
        );

        $dispatcher = app(DocumentProcessingDispatcher::class);

        $dispatcher->dispatchInitial($document, ProcessingProfile::Cloud);

        try {
            $dispatcher->dispatchInitial(
                $document,
                ProcessingProfile::HybridLocal,
            );

            $this->fail('Expected duplicate initial dispatch to be rejected.');
        } catch (LogicException) {
            // Expected.
        }

        $this->assertDatabaseCount('document_processing_runs', 1);
        Queue::assertPushed(ProcessDocumentJob::class, 1);
    }

    public function test_it_rejects_processing_while_file_is_still_quarantined(): void
    {
        Queue::fake();
        Storage::fake('documents');
        Storage::fake('document_quarantine');

        $document = app(DocumentStorageService::class)->storeQuarantined(
            User::factory()->create(),
            UploadedFile::fake()->createWithContent(
                'notes.txt',
                "Quarantined document content.\n",
            ),
        );

        try {
            app(DocumentProcessingDispatcher::class)->dispatchInitial(
                $document,
                ProcessingProfile::Cloud,
            );

            $this->fail('Expected quarantined document to be rejected.');
        } catch (RuntimeException) {
            // Expected.
        }

        $this->assertDatabaseCount('document_processing_runs', 0);
        Queue::assertNotPushed(ProcessDocumentJob::class);
    }
}
