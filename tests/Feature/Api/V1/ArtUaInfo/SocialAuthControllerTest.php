<?php

namespace Tests\Feature\Api\V1\ArtUaInfo;

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
        config(['services.google.redirect_art_ua_info' => 'https://art-ua-info.ddev.site:3000/auth/google/callback']);
    }

    private function fakeGoogleUser(string $email, string $name = 'Google Artist', ?string $avatar = null): SocialiteUser
    {
        $socialiteUser = new SocialiteUser;
        $socialiteUser->map(['id' => '123', 'name' => $name, 'email' => $email, 'avatar' => $avatar]);

        return $socialiteUser;
    }

    public function test_redirect_uses_art_ua_info_specific_redirect_uri(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('redirectUrl')
            ->once()
            ->with('https://art-ua-info.ddev.site:3000/auth/google/callback')
            ->andReturnSelf();
        $driver->shouldReceive('redirect')->andReturnSelf();
        $driver->shouldReceive('getTargetUrl')->andReturn('https://accounts.google.com/fake-auth-url');
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->getJson('/api/v1/art-ua-info/auth/google/redirect');

        $response->assertOk()->assertJson(['url' => 'https://accounts.google.com/fake-auth-url']);
    }

    public function test_new_google_user_via_art_ua_info_defaults_to_artist_profile_type(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('userFromToken')->andReturn($this->fakeGoogleUser('new-art-ua-info-google@example.com'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->postJson('/api/v1/art-ua-info/auth/google/callback', [
            'code' => 'fake-code',
            'access_token' => 'fake-access-token',
        ]);

        $response->assertOk()->assertJsonStructure(['message', 'user', 'token']);

        $user = User::where('email', 'new-art-ua-info-google@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(ProfileType::Artist, $user->profile_type);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_new_google_user_via_art_ua_info_downloads_avatar(): void
    {
        Storage::fake('public');
        Http::fake([
            'lh3.googleusercontent.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('userFromToken')->andReturn($this->fakeGoogleUser(
            'avatar-art-ua-info-google@example.com',
            avatar: 'https://lh3.googleusercontent.com/a/fake-photo'
        ));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->postJson('/api/v1/art-ua-info/auth/google/callback', [
            'code' => 'fake-code',
            'access_token' => 'fake-access-token',
        ])->assertOk();

        $user = User::where('email', 'avatar-art-ua-info-google@example.com')->firstOrFail();

        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_existing_blocked_google_user_is_rejected(): void
    {
        User::factory()->artist()->create([
            'email' => 'blocked-art-ua-info@example.com',
            'is_blocked' => true,
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('userFromToken')->andReturn($this->fakeGoogleUser('blocked-art-ua-info@example.com'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->postJson('/api/v1/art-ua-info/auth/google/callback', [
            'code' => 'fake-code',
            'access_token' => 'fake-access-token',
        ]);

        $response->assertStatus(403);
    }
}
