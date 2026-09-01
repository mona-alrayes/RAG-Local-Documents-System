<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunStatus;
use App\Jobs\ProcessDocumentJob;
use App\Jobs\ScanDocumentSecurityJob;
use App\Models\Document;
use App\Models\ProcessingRun;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTemporaryUploadFlowTest extends TestCase
{
    use DatabaseMigrations;

    public function test_valid_upload_is_stored_in_private_quarantine_only(): void
    {
        Queue::fake();

        Storage::fake('documents');
        Storage::fake('document_quarantine');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/documents', [
                'document' => UploadedFile::fake()->createWithContent(
                    'notes.txt',
                    "Temporary document content.\n",
                ),
                'processing_profile' => 'cloud',
            ])
            ->assertNoContent();

        $document = Document::query()->sole();

        $this->assertSame(
            DocumentStatus::Pending,
            $document->status,
        );

        Queue::assertPushed(
            ScanDocumentSecurityJob::class,
            fn (ScanDocumentSecurityJob $job) => $job->document->is($document)
                && $job->processingProfile === ProcessingProfile::Cloud,
        );

        Queue::assertNotPushed(ProcessDocumentJob::class);
        $this->assertDatabaseCount('document_processing_runs', 0);

        Storage::disk('document_quarantine')
            ->assertExists($document->file_path);

        Storage::disk('documents')
            ->assertMissing($document->file_path);
    }

    public function test_security_scan_can_be_explicitly_disabled_for_direct_permanent_storage(): void
    {
        Queue::fake();

        Storage::fake('documents');
        Storage::fake('document_quarantine');

        config()->set(
            'security.document_security_scan.enabled',
            false,
        );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/documents', [
                'document' => UploadedFile::fake()->createWithContent(
                    'notes.txt',
                    "Direct permanent document content.\n",
                ),
                'processing_profile' => 'hybrid_local',
            ])
            ->assertNoContent();

        $document = Document::query()->sole();

        $this->assertSame(
            DocumentStatus::Queued,
            $document->status,
        );

        Queue::assertNotPushed(ScanDocumentSecurityJob::class);

        $processingRun = ProcessingRun::query()->sole();

        $this->assertSame($document->id, $processingRun->document_id);
        $this->assertSame(
            ProcessingProfile::HybridLocal,
            $processingRun->profile,
        );
        $this->assertSame(
            ProcessingRunStatus::Pending,
            $processingRun->status,
        );

        Queue::assertPushed(
            ProcessDocumentJob::class,
            fn (ProcessDocumentJob $job) => $job->processingRunId
                === $processingRun->id,
        );

        Storage::disk('documents')
            ->assertExists($document->file_path);

        Storage::disk('document_quarantine')
            ->assertMissing($document->file_path);
    }
}
