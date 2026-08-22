<?php

namespace Tests\Feature\Documents;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentPrivateStorageDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_private_stored_document(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();

        $content = "Private UTF-8 document content.\n";
        $storedName = Str::ulid().'.txt';
        $filePath = $user->id.'/'.$storedName;

        Storage::disk('documents')->put(
            $filePath,
            $content,
        );

        $document = $user->documents()->create([
            'original_name' => 'notes.txt',
            'stored_name' => $storedName,
            'file_path' => $filePath,
            'file_type' => 'txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($content),
            'sha256' => hash('sha256', $content),
        ]);

        $this->actingAs($user)
            ->get("/documents/{$document->id}/download")
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
            'sha256' => hash('sha256', 'private document'),
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
