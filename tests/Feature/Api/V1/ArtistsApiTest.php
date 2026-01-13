<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

class ArtistsApiTest extends ApiTestCase
{
    // ==========================================
    // Список митців
    // ==========================================

    public function test_can_get_artists_list(): void
    {
        // Створюємо користувачів з проектами (митці)
        $artist = User::factory()->create();
        Project::factory()->create([
            'user_id' => $artist->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/artists');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
            ]);
    }

    public function test_artists_are_paginated(): void
    {
        // Створюємо митців
        for ($i = 0; $i < 15; $i++) {
            $artist = User::factory()->create();
            Project::factory()->create([
                'user_id' => $artist->id,
                'status' => ProjectStatus::InProgress,
            ]);
        }

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/artists?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data');
    }

    // ==========================================
    // Профіль митця
    // ==========================================

    public function test_can_get_artist_profile(): void
    {
        $artist = User::factory()->create();
        Project::factory()->create([
            'user_id' => $artist->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/artists/{$artist->slug}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'avatar_url',
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_artist(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/artists/nonexistent-slug');

        $response->assertNotFound();
    }

    // ==========================================
    // Проекти митця
    // ==========================================

    public function test_can_get_artist_projects(): void
    {
        $artist = User::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $artist->id,
            'status' => ProjectStatus::InProgress,
        ]);
        // Чернетка не показується
        Project::factory()->create([
            'user_id' => $artist->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/artists/{$artist->slug}/projects");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_artist_projects_only_shows_public(): void
    {
        $artist = User::factory()->create();
        Project::factory()->create([
            'user_id' => $artist->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/artists/{$artist->slug}/projects");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
