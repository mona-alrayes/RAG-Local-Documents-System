<?php

namespace Tests\Feature\Conversations;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_only_sees_their_own_conversations(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->conversations()->create([
            'title' => 'محادثتي الخاصة',
        ]);

        $otherUser->conversations()->create([
            'title' => 'محادثة مستخدم آخر',
        ]);

        $this
            ->actingAs($user)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertSee('محادثتي الخاصة')
            ->assertDontSee('محادثة مستخدم آخر');
    }

    public function test_authenticated_user_can_create_a_conversation_owned_by_them(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->post(route('conversations.store'), [
                'title' => 'محادثة جديدة',
                'user_id' => $otherUser->id,
            ])
            ->assertRedirect(route('conversations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'title' => 'محادثة جديدة',
        ]);

        $this->assertDatabaseMissing('conversations', [
            'user_id' => $otherUser->id,
            'title' => 'محادثة جديدة',
        ]);
    }

    public function test_conversation_title_is_optional(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->post(route('conversations.store'), [])
            ->assertRedirect(route('conversations.index'));

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'title' => null,
        ]);
    }

    public function test_conversation_title_must_not_exceed_255_characters(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->post(route('conversations.store'), [
                'title' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_guest_cannot_access_or_create_conversations(): void
    {
        $this
            ->get(route('conversations.index'))
            ->assertRedirect(route('login'));

        $this
            ->post(route('conversations.store'), [
                'title' => 'Guest conversation',
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('conversations', 0);
    }
}
