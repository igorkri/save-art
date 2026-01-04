<?php

namespace Database\Seeders;

use App\Models\ArtistBoard;
use Illuminate\Database\Seeder;

class ArtistBoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Пропускаємо якщо вже є запис
        if (ArtistBoard::exists()) {
            return;
        }

        ArtistBoard::create([
            'titles' => [
                'uk' => 'Художня рада',
                'en' => 'Artist Board',
            ],
            'logo_museums' => [
                [
                    'logo' => 'museums/museum1.webp',
                    'name' => ['uk' => 'Національний музей', 'en' => 'National Museum'],
                ],
                [
                    'logo' => 'museums/museum2.webp',
                    'name' => ['uk' => 'Музей мистецтв', 'en' => 'Art Museum'],
                ],
            ],
            'descriptions' => [
                'uk' => 'Художня рада платформи ID_Art UA складається з провідних експертів у галузі мистецтва, кураторів та колекціонерів.',
                'en' => 'The Artist Board of ID_Art UA platform consists of leading art experts, curators and collectors.',
            ],
            'data' => [
                'members' => [
                    [
                        'name' => ['uk' => 'Іван Петренко', 'en' => 'Ivan Petrenko'],
                        'role' => ['uk' => 'Голова ради', 'en' => 'Board Chairman'],
                        'photo' => 'board/member1.webp',
                    ],
                    [
                        'name' => ['uk' => 'Олена Коваленко', 'en' => 'Olena Kovalenko'],
                        'role' => ['uk' => 'Куратор', 'en' => 'Curator'],
                        'photo' => 'board/member2.webp',
                    ],
                    [
                        'name' => ['uk' => 'Михайло Шевченко', 'en' => 'Mykhailo Shevchenko'],
                        'role' => ['uk' => 'Мистецтвознавець', 'en' => 'Art Historian'],
                        'photo' => 'board/member3.webp',
                    ],
                ],
            ],
        ]);
    }
}
