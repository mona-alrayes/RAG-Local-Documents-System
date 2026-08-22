<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTemporaryUploadFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_upload_is_stored_in_private_quarantine_only(): void
    {
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

        Storage::disk('document_quarantine')
            ->assertExists($document->file_path);

        Storage::disk('documents')
            ->assertMissing($document->file_path);
    }
}
