<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\ProfileLegal;
use App\Models\ProfileSocial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Вимикаємо перевірку API key для тестів
        config(['services.api_key' => '']);
    }

    public function test_get_profile_unauthenticated(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }

    public function test_get_profile_returns_user_and_profiles(): void
    {
        $user = User::factory()->create([
            'avatar' => 'test.jpg',
            'full_name' => 'Тест',
        ]);
        ProfileLegal::factory()->create(['user_id' => $user->id]);
        ProfileSocial::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'email'],
                'profilePersonal' => ['id', 'user_id', 'avatar', 'full_name'],
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
            'full_name' => 'John Doe',
        ];

        $this->postJson('/api/v1/profile/personal', $payload)
            ->assertCreated()
            ->assertJsonPath('profilePersonal.avatar', '/storage/path.jpg');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar' => 'path.jpg',
        ]);
    }

    public function test_update_personal_profile(): void
    {
        $user = User::factory()->create(['avatar' => 'old.jpg']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile/personal', ['avatar' => 'new.jpg'])
            ->assertOk()
            ->assertJsonPath('profilePersonal.avatar', '/storage/new.jpg');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar' => 'new.jpg',
        ]);
    }

    public function test_create_and_update_legal_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'currency' => 'USD',
            'name' => 'Тестова компанія',
        ];
        $this->postJson('/api/v1/profile/legal', $payload)
            ->assertCreated()
            ->assertJson(['profileLegal' => ['currency' => 'USD']]);

        // Повторний POST ідемпотентно оновлює наявний профіль
        $this->postJson('/api/v1/profile/legal', $payload)
            ->assertOk();

        // update
        $this->putJson('/api/v1/profile/legal', ['currency' => 'EUR'])
            ->assertOk()
            ->assertJson(['profileLegal' => ['currency' => 'EUR']]);
    }

    public function test_create_and_update_social_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = ['facebook' => 'https://fb.com/user'];
        $this->postJson('/api/v1/profile/social', $payload)
            ->assertCreated()
            ->assertJson(['profileSocial' => ['facebook' => 'https://fb.com/user']]);

        // conflict
        $this->postJson('/api/v1/profile/social', $payload)
            ->assertStatus(409);

        // update
        $this->putJson('/api/v1/profile/social', ['instagram' => 'https://insta.com/user'])
            ->assertOk()
            ->assertJson(['profileSocial' => ['instagram' => 'https://insta.com/user']]);
    }

    public function test_new_project_hint_is_unseen_by_default(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJson(['user' => ['has_seen_new_project_hint' => false]]);
    }

    public function test_can_mark_new_project_hint_as_seen(): void
    {
        $user = User::factory()->create(['has_seen_new_project_hint' => false]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/new-project-hint-seen')
            ->assertOk()
            ->assertJson(['has_seen_new_project_hint' => true]);

        $this->assertTrue($user->fresh()->has_seen_new_project_hint);

        // Стан має зберегтись і після перезавантаження профілю (не тільки в одній відповіді).
        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJson(['user' => ['has_seen_new_project_hint' => true]]);
    }

    public function test_mark_new_project_hint_seen_requires_authentication(): void
    {
        $this->postJson('/api/v1/profile/new-project-hint-seen')
            ->assertUnauthorized();
    }

    public function test_deletion_request_with_pending_donation_notifies_user_instead_of_deleting(): void
    {
        $user = User::factory()->create();
        Donation::factory()->create(['user_id' => $user->id]); // pending за замовчуванням
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile')
            ->assertOk()
            ->assertJson(['has_pending_operations' => true]);

        $this->assertNotNull($user->fresh()->deletion_requested_at);

        $notification = Notification::where('user_id', $user->id)
            ->where('type', NotificationType::System)
            ->first();

        $this->assertNotNull($notification);
        $this->assertIsString($notification->title['uk']);
        $this->assertIsString($notification->message['uk']);
    }
}
