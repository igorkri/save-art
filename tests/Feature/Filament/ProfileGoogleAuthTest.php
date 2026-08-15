<?php

namespace Tests\Feature\Filament;

use App\Enums\ProfileType;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileGoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $email, string $name = 'Google Artist'): SocialiteUser
    {
        $socialiteUser = new SocialiteUser;
        $socialiteUser->map(['id' => '123', 'name' => $name, 'email' => $email]);

        return $socialiteUser;
    }

    public function test_new_google_user_is_registered_as_artist_and_logged_in(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($this->fakeGoogleUser('new-google@example.com'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->get(route('profile.auth.google.callback'));

        $user = User::where('email', 'new-google@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(ProfileType::Artist, $user->profile_type);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect();
    }

    public function test_existing_blocked_google_user_is_rejected(): void
    {
        User::factory()->artist()->create([
            'email' => 'blocked@example.com',
            'is_blocked' => true,
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($this->fakeGoogleUser('blocked@example.com'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get(route('profile.auth.google.callback'));

        $this->assertGuest();
    }
}
