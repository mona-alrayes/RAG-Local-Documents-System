<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentSecurityScanStatus;
use App\Enums\DocumentStatus;
use App\Jobs\ScanDocumentSecurityJob;
use App\Models\User;
use App\Services\Documents\DocumentSecurityService;
use App\Services\Documents\DocumentStorageService;
use App\Services\Documents\DocumentUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ScanDocumentSecurityJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_clean_scan_updates_status_and_promotes_document(): void
    {
        Storage::fake('documents');
        Storage::fake('document_quarantine');

        $user = User::factory()->create();

        $document = app(DocumentStorageService::class)
            ->storeQuarantined(
                $user,
                UploadedFile::fake()->createWithContent(
                    'notes.txt',
                    "Clean document content.\n",
                ),
            );

        $document->refresh();

        $this->assertSame(
            DocumentStatus::Pending,
            $document->status,
        );

        $this->mock(
            DocumentSecurityService::class,
            function (MockInterface $mock) use ($document): void {
                $mock->shouldReceive('scan')
                    ->once()
                    ->andReturnUsing(
                        function (string $filePath) use ($document): DocumentSecurityScanStatus {
                            $this->assertSame(
                                DocumentStatus::Scanning,
                                $document->fresh()->status,
                            );

                            $this->assertSame(
                                Storage::disk('document_quarantine')
                                    ->path($document->file_path),
                                $filePath,
                            );

                            return DocumentSecurityScanStatus::Clean;
                        },
                    );
            },
        );

        $job = new ScanDocumentSecurityJob($document);

        $job->handle(
            app(DocumentSecurityService::class),
            app(DocumentUploadService::class),
        );

        $document->refresh();

        $this->assertSame(
            DocumentStatus::Pending,
            $document->status,
        );

        Storage::disk('documents')
            ->assertExists($document->file_path);

        Storage::disk('document_quarantine')
            ->assertMissing($document->file_path);
    }
}
