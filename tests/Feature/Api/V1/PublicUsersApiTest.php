<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DonationStatus;
use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;

class PublicUsersApiTest extends ApiTestCase
{
    // ==========================================
    // Профіль користувача
    // ==========================================

    public function test_can_get_public_user_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/users/{$user->id}");

        // Формат: data.user.id
        $response->assertOk()
            ->assertJsonStructure([
                'result',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'avatar',
                        'statistics',
                    ],
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_user(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/users/99999');

        $response->assertNotFound();
    }

    public function test_user_profile_does_not_expose_email(): void
    {
        $user = User::factory()->create([
            'email' => 'private@example.com',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/users/{$user->id}");

        $response->assertOk()
            ->assertJsonMissing(['email' => 'private@example.com']);
    }

    // ==========================================
    // Проекти користувача
    // ==========================================

    public function test_can_get_user_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/users/{$user->id}/projects");

        // Формат: data.projects
        $response->assertOk()
            ->assertJsonCount(3, 'data.projects');
    }

    public function test_user_projects_only_shows_public(): void
    {
        $user = User::factory()->create();

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::InProgress,
        ]);
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft,
        ]);
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/users/{$user->id}/projects");

        $response->assertOk();
        // Draft не показується (приватний), InProgress та Announced показуються (публічні)
        $this->assertCount(2, $response->json('data.projects'));
    }

    public function test_returns_empty_for_user_without_projects(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/users/{$user->id}/projects");

        // Формат: data.projects
        $response->assertOk()
            ->assertJsonCount(0, 'data.projects');
    }

    // ==========================================
    // Публічні донати користувача (меценатство)
    // ==========================================

    public function test_can_get_user_public_donations(): void
    {
        $user = User::factory()->create();
        Donation::factory()->count(2)->create([
            'user_id' => $user->id,
            'donation_type' => Donation::TYPE_PROJECT,
            'status' => DonationStatus::Paid,
            'is_anonymous' => false,
            'is_public' => true,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/users/{$user->id}/donations");

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_public_donations_hide_pending_anonymous_and_hidden(): void
    {
        $user = User::factory()->create();

        Donation::factory()->create([
            'user_id' => $user->id,
            'donation_type' => Donation::TYPE_PROJECT,
            'status' => DonationStatus::Paid,
            'is_anonymous' => false,
            'is_public' => true,
        ]);
        // Ще не оплачений
        Donation::factory()->create([
            'user_id' => $user->id,
            'donation_type' => Donation::TYPE_PROJECT,
            'status' => DonationStatus::Pending,
            'is_anonymous' => false,
            'is_public' => true,
        ]);
        // Анонімний
        Donation::factory()->create([
            'user_id' => $user->id,
            'donation_type' => Donation::TYPE_PROJECT,
            'status' => DonationStatus::Paid,
            'is_anonymous' => true,
            'is_public' => true,
        ]);
        // Приховано користувачем
        Donation::factory()->create([
            'user_id' => $user->id,
            'donation_type' => Donation::TYPE_PROJECT,
            'status' => DonationStatus::Paid,
            'is_anonymous' => false,
            'is_public' => false,
        ]);
        // Донат на платформу (без проєкту)
        Donation::factory()->create([
            'user_id' => $user->id,
            'donation_type' => Donation::TYPE_PLATFORM,
            'status' => DonationStatus::Paid,
            'is_anonymous' => false,
            'is_public' => true,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/users/{$user->id}/donations");

        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
