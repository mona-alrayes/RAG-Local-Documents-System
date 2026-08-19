<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentPrivateStorageDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_upload_is_privately_stored_owned_and_downloadable_by_owner(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();

        $content = "Private UTF-8 document content.\n";

        $upload = UploadedFile::fake()->createWithContent(
            'notes.txt',
            $content,
        );

        $this->actingAs($user)
            ->post('/documents', [
                'document' => $upload,
            ])
            ->assertNoContent();
        $document = Document::query()->sole();

        $this->assertTrue($document->user->is($user));
        $this->assertSame('notes.txt', $document->original_name);
        $this->assertSame('txt', $document->file_type->value);
        $this->assertSame('text/plain', $document->mime_type);
        $this->assertSame(strlen($content), $document->file_size);

        $this->assertNotSame(
            $document->original_name,
            $document->stored_name,
        );

        $this->assertTrue(
            Str::isUlid(
                pathinfo($document->stored_name, PATHINFO_FILENAME),
            ),
        );

        $this->assertSame(
            'txt',
            pathinfo($document->stored_name, PATHINFO_EXTENSION),
        );

        $this->assertSame(
            $document->stored_name,
            basename($document->file_path),
        );

        $this->assertStringStartsWith(
            $user->id.'/',
            $document->file_path,
        );

        $this->assertStringNotContainsString(
            'notes',
            $document->file_path,
        );

        Storage::disk('documents')
            ->assertExists($document->file_path);

        $download = $this->get(
            "/documents/{$document->id}/download",
        );

        $download
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload('notes.txt');
    }

    public function test_other_user_cannot_download_or_directly_access_private_document(): void
    {
        Storage::fake('documents');
        Storage::fake('public');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $storedName = Str::ulid().'.txt';
        $filePath = $owner->id.'/'.$storedName;

        Storage::disk('documents')->put(
            $filePath,
            'private document',
        );

        $document = $owner->documents()->create([
            'original_name' => 'private.txt',
            'stored_name' => $storedName,
            'file_path' => $filePath,
            'file_type' => 'txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen('private document'),
        ]);

        $this->actingAs($otherUser)
            ->get("/documents/{$document->id}/download")
            ->assertForbidden();

        $directAccess = $this->get(
            '/storage/'.$document->file_path,
        );

        $this->assertContains(
            $directAccess->status(),
            [403, 404],
        );

        Storage::disk('public')
            ->assertMissing($document->file_path);

        Storage::disk('documents')
            ->assertExists($document->file_path);
    }
}
