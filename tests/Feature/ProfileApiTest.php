<?php

namespace Tests\Feature;

use App\Models\ProfileLegal;
use App\Models\ProfilePersonal;
use App\Models\ProfileSocial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_profile_unauthenticated(): void
    {
        $this->getJson('/api/profile')
            ->assertUnauthorized();
    }

    public function test_get_profile_returns_user_and_profiles(): void
    {
        $user = User::factory()->create();
        ProfilePersonal::factory()->create(['user_id' => $user->id]);
        ProfileLegal::factory()->create(['user_id' => $user->id]);
        ProfileSocial::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'profilePersonal' => ['id', 'user_id', 'avatar'],
                'profileLegal' => ['id', 'user_id', 'currency'],
                'profileSocial' => ['id', 'user_id', 'website'],
            ]);
    }

    public function test_create_personal_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'avatar' => 'path.jpg',
            'full_name' => ['en' => 'John Doe'],
        ];

        $this->postJson('/api/profile/personal', $payload)
            ->assertCreated()
            ->assertJson(['profilePersonal' => ['avatar' => 'path.jpg']]);

        $this->assertDatabaseHas('profile_personals', [
            'user_id' => $user->id,
            'avatar' => 'path.jpg',
        ]);
    }

    public function test_create_personal_profile_conflict(): void
    {
        $user = User::factory()->create();
        ProfilePersonal::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/profile/personal', [])
            ->assertConflict();
    }

    public function test_update_personal_profile(): void
    {
        $user = User::factory()->create();
        ProfilePersonal::factory()->create(['user_id' => $user->id, 'avatar' => 'old.jpg']);
        Sanctum::actingAs($user);

        $this->putJson('/api/profile/personal', ['avatar' => 'new.jpg'])
            ->assertOk()
            ->assertJson(['profilePersonal' => ['avatar' => 'new.jpg']]);

        $this->assertDatabaseHas('profile_personals', [
            'user_id' => $user->id,
            'avatar' => 'new.jpg',
        ]);
    }

    public function test_create_and_update_legal_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'currency' => 'USD',
            'is_legal' => true,
            'name' => ['uk' => 'Тестова компанія', 'en' => 'Test Company'],
        ];
        $this->postJson('/api/profile/legal', $payload)
            ->assertCreated()
            ->assertJson(['profileLegal' => ['currency' => 'USD', 'is_legal' => true]]);

        // conflict
        $this->postJson('/api/profile/legal', $payload)
            ->assertStatus(409);

        // update
        $this->putJson('/api/profile/legal', ['currency' => 'EUR'])
            ->assertOk()
            ->assertJson(['profileLegal' => ['currency' => 'EUR']]);
    }

    public function test_create_and_update_social_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = ['facebook' => 'https://fb.com/user'];
        $this->postJson('/api/profile/social', $payload)
            ->assertCreated()
            ->assertJson(['profileSocial' => ['facebook' => 'https://fb.com/user']]);

        // conflict
        $this->postJson('/api/profile/social', $payload)
            ->assertStatus(409);

        // update
        $this->putJson('/api/profile/social', ['instagram' => 'https://insta.com/user'])
            ->assertOk()
            ->assertJson(['profileSocial' => ['instagram' => 'https://insta.com/user']]);
    }
}
