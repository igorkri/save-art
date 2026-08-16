<?php

namespace Tests\Feature\Filament;

use App\Enums\ProfileType;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileGoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $email, string $name = 'Google Artist', ?string $avatar = null): SocialiteUser
    {
        $socialiteUser = new SocialiteUser;
        $socialiteUser->map(['id' => '123', 'name' => $name, 'email' => $email, 'avatar' => $avatar]);

        return $socialiteUser;
    }

    public function test_new_google_user_is_registered_as_artist_and_logged_in(): void
    {
        Storage::fake('public');
        Http::fake([
            'lh3.googleusercontent.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($this->fakeGoogleUser(
            'new-google@example.com',
            avatar: 'https://lh3.googleusercontent.com/a/fake-photo'
        ));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->get(route('profile.auth.google.callback'));

        $user = User::where('email', 'new-google@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(ProfileType::Artist, $user->profile_type);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect();

        // Аватар з Google завантажується і зберігається на диску, а не лишається
        // зовнішнім URL — інакше Storage::url($user->avatar) поламався б.
        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
        Http::assertSent(fn (HttpClientRequest $request) => $request->url() === 'https://lh3.googleusercontent.com/a/fake-photo');
    }

    public function test_new_google_user_without_avatar_is_registered_without_avatar(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($this->fakeGoogleUser('no-avatar@example.com'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get(route('profile.auth.google.callback'));

        $user = User::where('email', 'no-avatar@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->avatar);
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
