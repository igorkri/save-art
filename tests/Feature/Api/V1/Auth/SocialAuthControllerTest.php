<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\ProfileType;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api_key' => '']);
    }

    private function fakeGoogleUser(string $email, string $name = 'Google Artist', ?string $avatar = null): SocialiteUser
    {
        $socialiteUser = new SocialiteUser;
        $socialiteUser->map(['id' => '123', 'name' => $name, 'email' => $email, 'avatar' => $avatar]);

        return $socialiteUser;
    }

    public function test_new_google_user_via_spa_defaults_to_artist_profile_type(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('userFromToken')->andReturn($this->fakeGoogleUser('new-spa-google@example.com'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'fake-code',
            'access_token' => 'fake-access-token',
        ]);

        $response->assertOk()->assertJsonStructure(['message', 'user', 'token']);

        $user = User::where('email', 'new-spa-google@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::User, $user->role);
        // profile_type = Artist за замовчуванням — без нього доступ до Filament-панелі
        // "profile" (User::canAccessPanel, лише isArtist()) одразу впирався в 403,
        // ще до того, як користувач встигав обрати роль на /choose-role.
        $this->assertSame(ProfileType::Artist, $user->profile_type);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_new_google_user_via_spa_downloads_avatar(): void
    {
        Storage::fake('public');
        Http::fake([
            'lh3.googleusercontent.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('userFromToken')->andReturn($this->fakeGoogleUser(
            'avatar-spa-google@example.com',
            avatar: 'https://lh3.googleusercontent.com/a/fake-photo'
        ));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'fake-code',
            'access_token' => 'fake-access-token',
        ])->assertOk();

        $user = User::where('email', 'avatar-spa-google@example.com')->firstOrFail();

        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }
}
