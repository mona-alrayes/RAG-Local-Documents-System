<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name_without_losing_email_verification(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('settings.account'))
            ->put(route('user-profile-information.update'), [
                'name' => 'الاسم الجديد',
                'email' => $user->email,
            ]);

        $response
            ->assertRedirect(route('settings.account'))
            ->assertSessionHas('status', 'profile-information-updated');

        $user->refresh();

        $this->assertSame('الاسم الجديد', $user->name);
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_changing_email_requires_verification_again(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), [
                'name' => $user->name,
                'email' => 'new-email@example.com',
            ])
            ->assertSessionHas('status', 'profile-information-updated');

        $user->refresh();

        $this->assertSame('new-email@example.com', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_must_be_unique_when_updating_profile(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('settings.account'))
            ->put(route('user-profile-information.update'), [
                'name' => $user->name,
                'email' => $otherUser->email,
            ]);

        $response
            ->assertRedirect(route('settings.account'))
            ->assertSessionHasErrorsIn(
                'updateProfileInformation',
                ['email'],
            );

        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('settings.account'))
            ->put(route('user-password.update'), [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response
            ->assertRedirect(route('settings.account'))
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(
            Hash::check('NewPassword123!', $user->fresh()->password)
        );
    }

    public function test_password_is_not_changed_when_current_password_is_wrong(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('settings.account'))
            ->put(route('user-password.update'), [
                'current_password' => 'WrongPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response
            ->assertRedirect(route('settings.account'))
            ->assertSessionHasErrorsIn(
                'updatePassword',
                ['current_password'],
            );

        $this->assertTrue(
            Hash::check('OldPassword123!', $user->fresh()->password)
        );
    }
}
