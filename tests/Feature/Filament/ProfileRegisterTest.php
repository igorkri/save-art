<?php

namespace Tests\Feature\Filament;

use App\Enums\ProfileType;
use App\Filament\Profile\Pages\Auth\Register;
use App\Models\User;
use App\UserRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_guest_can_register_and_gains_access_to_profile_panel(): void
    {
        Livewire::test(Register::class)
            ->set('data.full_name', 'Новий Митець')
            ->set('data.email', 'new-artist@example.com')
            ->set('data.password', 'SecurePass123')
            ->set('data.passwordConfirmation', 'SecurePass123')
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'new-artist@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Новий Митець', $user->full_name);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(ProfileType::Artist, $user->profile_type);
        $this->assertNotNull($user->slug);
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('profile')));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_requires_matching_passwords(): void
    {
        Livewire::test(Register::class)
            ->set('data.full_name', 'Новий Митець')
            ->set('data.email', 'mismatch@example.com')
            ->set('data.password', 'SecurePass123')
            ->set('data.passwordConfirmation', 'DifferentPass123')
            ->call('register')
            ->assertHasFormErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.com']);
    }
}
