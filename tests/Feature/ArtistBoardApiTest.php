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
                'title1' => [
                    'uk' => 'Спецпроєкт',
                    'en' => 'Special Project'
                ],
                'title2' => [
                    'uk' => '10 художників в 10 національних музеях світу',
                    'en' => '10 artists in 10 national museums of the world'
                ],
                'description' => [
                    'uk' => 'Короткий опис спецпроєкту українською',
                    'en' => 'Short description of the special project in English'
                ]
            ],
            'descriptions' => [
                'uk' => '<p>Повний опис українською</p>',
                'en' => '<p>Full description in English</p>'
            ],
            'logo_museums' => [],
            'data' => []
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
                        'description'
                    ],
                    'descriptions',
                    'logo_museums',
                    'data',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'result' => true,
                'data' => [
                    'titles' => [
                        'title1' => [
                            'uk' => 'Спецпроєкт',
                            'en' => 'Special Project'
                        ],
                        'title2' => [
                            'uk' => '10 художників в 10 національних музеях світу',
                            'en' => '10 artists in 10 national museums of the world'
                        ],
                        'description' => [
                            'uk' => 'Короткий опис спецпроєкту українською',
                            'en' => 'Short description of the special project in English'
                        ]
                    ]
                ]
            ]);
    }

    public function test_artist_board_api_with_language_parameter(): void
    {
        config(['services.api_key' => 'test-api-key']);

        ArtistBoard::create([
            'titles' => [
                'title1' => [
                    'uk' => 'Спецпроєкт',
                    'en' => 'Special Project'
                ],
                'title2' => [
                    'uk' => '10 художників в 10 національних музеях світу',
                    'en' => '10 artists in 10 national museums of the world'
                ],
                'description' => [
                    'uk' => 'Короткий опис спецпроєкту українською',
                    'en' => 'Short description of the special project in English'
                ]
            ],
            'descriptions' => [
                'uk' => '<p>Повний опис українською</p>',
                'en' => '<p>Full description in English</p>'
            ],
            'logo_museums' => [],
            'data' => []
        ]);

        $response = $this->withHeaders(['X-Api-Key' => 'test-api-key'])
            ->get('/api/artist-board?language=uk');

        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
                'language' => 'uk'
            ]);

        // Проверяем что поле description присутствует в ответе
        $data = $response->json('data');
        $this->assertArrayHasKey('titles', $data);
        $this->assertArrayHasKey('description', $data['titles']);
    }
}
