<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_app_pages_render_the_shared_navigation_shell(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        foreach ([
            route('workspace'),
            route('documents.index'),
            route('settings.account'),
        ] as $url) {
            $this
                ->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('مساحة العمل')
                ->assertSee('الوثائق')
                ->assertSee('إعدادات الحساب')
                ->assertSee('تسجيل الخروج')
                ->assertSee($user->name);
        }
    }
}
