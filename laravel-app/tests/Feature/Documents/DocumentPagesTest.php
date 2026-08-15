<?php

namespace Tests\Feature\Documents;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_displays_the_authenticated_users_documents(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $owner->documents()->create([
            'original_name' => 'owner-document.pdf',
            'stored_name' => 'owner-stored.pdf',
            'file_path' => 'documents/owner-stored.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'sha256' => str_repeat('a', 64),
        ]);

        $otherUser->documents()->create([
            'original_name' => 'other-document.pdf',
            'stored_name' => 'other-stored.pdf',
            'file_path' => 'documents/other-stored.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'sha256' => str_repeat('b', 64),
        ]);

        $response = $this->actingAs($owner)->get('/documents');

        $response
            ->assertOk()
            ->assertSee('owner-document.pdf')
            ->assertDontSee('other-document.pdf');
    }

    public function test_owner_can_view_their_document_details(): void
    {
        $owner = User::factory()->create();

        $document = $owner->documents()->create([
            'original_name' => 'owner-details.pdf',
            'stored_name' => 'owner-details-stored.pdf',
            'file_path' => 'documents/owner-details-stored.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'sha256' => str_repeat('c', 64),
        ]);

        $response = $this
            ->actingAs($owner)
            ->get("/documents/{$document->id}");

        $response
            ->assertOk()
            ->assertSee('owner-details.pdf');
    }

    public function test_user_cannot_view_another_users_document_details(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $document = $owner->documents()->create([
            'original_name' => 'private-document.pdf',
            'stored_name' => 'private-document-stored.pdf',
            'file_path' => 'documents/private-document-stored.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'sha256' => str_repeat('d', 64),
        ]);

        $this
            ->actingAs($otherUser)
            ->get("/documents/{$document->id}")
            ->assertForbidden();
    }
}
