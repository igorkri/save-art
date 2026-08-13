<?php

namespace Tests\Feature;

use App\Models\ArtistBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistBoardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_board_api_returns_titles_with_description(): void
    {
        config(['services.api_key' => 'test-api-key']);

        // Создаем тестовые данные ArtistBoard с новым полем description
        ArtistBoard::create([
            'titles' => [
                'title1' => 'Спецпроєкт',
                'title2' => '10 художників в 10 національних музеях світу',
                'description' => 'Короткий опис спецпроєкту',
            ],
            'descriptions' => '<p>Повний опис</p>',
            'logo_museums' => [],
            'data' => [],
        ]);

        $response = $this->withHeaders(['X-Api-Key' => 'test-api-key'])
            ->get('/api/artist-board');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'message',
                'data' => [
                    'id',
                    'titles' => [
                        'title1',
                        'title2',
                        'description',
                    ],
                    'descriptions',
                    'logo_museums',
                    'data',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'result' => true,
                'data' => [
                    'titles' => [
                        'title1' => 'Спецпроєкт',
                        'title2' => '10 художників в 10 національних музеях світу',
                        'description' => 'Короткий опис спецпроєкту',
                    ],
                ],
            ]);
    }
}
