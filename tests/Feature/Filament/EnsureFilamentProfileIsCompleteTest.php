<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class EnsureFilamentProfileIsCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_without_completed_profile_is_redirected_to_edit_profile(): void
    {
        $user = User::factory()->artist()->create();
        $this->actingAs($user);

        $this->get('/profile/donations')
            ->assertRedirect('/profile/profile');
    }

    public function test_redirect_carries_a_warning_notification_explaining_why(): void
    {
        $user = User::factory()->artist()->create();
        $this->actingAs($user);

        $this->get('/profile/donations');

        $notifications = session('filament.notifications');
        $this->assertNotEmpty($notifications);
        $this->assertSame('warning', $notifications[0]['status']);
        $this->assertSame(__('profile_edit.completion_required.title'), $notifications[0]['title']);
    }

    public function test_artist_without_completed_profile_can_still_reach_edit_profile_page(): void
    {
        $user = User::factory()->artist()->create();
        $this->actingAs($user);

        $this->get('/profile/profile')->assertOk();
    }

    public function test_artist_without_completed_profile_can_still_logout(): void
    {
        $user = User::factory()->artist()->create();
        $this->actingAs($user);

        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->post('/profile/logout')->assertRedirect();
        $this->assertGuest('web');
    }

    public function test_artist_with_completed_profile_can_access_other_pages(): void
    {
        $user = User::factory()->artist()->profileCompleted()->create();
        $this->actingAs($user);

        $this->get('/profile/donations')->assertOk();
    }

    public function test_is_profile_complete_reflects_profile_completed_at(): void
    {
        $user = User::factory()->artist()->create();
        $this->assertFalse($user->isProfileComplete());

        $user->forceFill(['profile_completed_at' => now()])->save();
        $this->assertTrue($user->fresh()->isProfileComplete());
    }
}
