<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Jobs\ScanDocumentSecurityJob;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTemporaryUploadFlowTest extends TestCase
{
    use RefreshDatabase;

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
            ])
            ->assertNoContent();

        $document = Document::query()->sole();

        $this->assertSame(
            DocumentStatus::Pending,
            $document->status,
        );

        Queue::assertPushed(
            ScanDocumentSecurityJob::class,
            fn (ScanDocumentSecurityJob $job) => $job->document->is($document),
        );

        Storage::disk('document_quarantine')
            ->assertExists($document->file_path);

        Storage::disk('documents')
            ->assertMissing($document->file_path);
    }

    public function test_security_scan_can_be_explicitly_disabled_for_direct_permanent_storage(): void
    {
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
            ])
            ->assertNoContent();

        $document = Document::query()->sole();

        Storage::disk('documents')
            ->assertExists($document->file_path);

        Storage::disk('document_quarantine')
            ->assertMissing($document->file_path);
    }
}
