<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentSha256DuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_stores_sha256_calculated_from_stored_content(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();
        $content = "SHA-256 document content.\n";

        $this->actingAs($user)
            ->postJson('/documents', [
                'document' => UploadedFile::fake()->createWithContent(
                    'report.txt',
                    $content,
                ),
            ])
            ->assertNoContent();

        $document = Document::query()->sole();

        $this->assertSame(
            hash('sha256', $content),
            $document->sha256,
        );

        Storage::disk('documents')
            ->assertExists($document->file_path);
    }

    public function test_duplicate_content_for_same_user_is_rejected_and_new_file_is_removed(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();
        $content = "Same document content.\n";

        $this->actingAs($user)
            ->postJson('/documents', [
                'document' => UploadedFile::fake()->createWithContent(
                    'original.txt',
                    $content,
                ),
            ])
            ->assertNoContent();

        $original = Document::query()->sole();

        $this->actingAs($user)
            ->postJson('/documents', [
                'document' => UploadedFile::fake()->createWithContent(
                    'renamed-copy.txt',
                    $content,
                ),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document')
            ->assertJsonPath(
                'duplicate_document.id',
                $original->id,
            )
            ->assertJsonPath(
                'duplicate_document.original_name',
                'original.txt',
            );

        $this->assertDatabaseCount('documents', 1);

        $this->assertCount(
            1,
            Storage::disk('documents')->allFiles(),
        );

        Storage::disk('documents')
            ->assertExists($original->file_path);
    }
}
