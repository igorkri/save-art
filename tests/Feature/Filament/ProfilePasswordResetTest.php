<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfilePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_receives_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->artist()->create(['email' => 'artist@example.com']);

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', 'artist@example.com')
            ->call('request');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_non_artist_does_not_receive_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'patron@example.com', 'profile_type' => null]);

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', 'patron@example.com')
            ->call('request');

        Notification::assertNotSentTo($user, ResetPasswordNotification::class);
    }
}
