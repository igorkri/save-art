<?php

namespace App\Console\Commands;

use App\Models\HomePage;
use Illuminate\Console\Command;

class GenerateHomePageTestData extends Command
{
    protected $signature = 'home-page:generate-test-data {--count=5 : Количество записей для создания}';

    protected $description = 'Генерирует тестовые данные для главной страницы';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        $this->info("Создаю {$count} тестовых записей для главной страницы...");

        // Деактивируем все существующие записи
        HomePage::query()->update(['is_active' => false]);

        for ($i = 1; $i <= $count; $i++) {
            $isActive = $i === 1; // Только первая запись активная

            HomePage::create([
                'hero_title' => [
                    'ua' => "Мистецтво як сила #{$i} — Відродження України",
                    'en' => "Art as Power #{$i} — Revival of Ukraine",
                ],
                'hero_video_poster' => "hero-videos/hero-{$i}.webp",
                'hero_video_url' => "videos/hero-video-{$i}.webm",

                'donates_subtitle' => [
                    'ua' => 'ПІДТРИМАЙ УКРАЇНСЬКЕ МИСТЕЦТВО СЬОГОДНІ',
                    'en' => 'SUPPORT UKRAINIAN ART TODAY',
                ],
                'donates_title' => [
                    'ua' => "Кожна гривня — це крок до перемоги #{$i}",
                    'en' => "Every hryvnia is a step towards victory #{$i}",
                ],
                'donates_text' => [
                    'ua' => [
                        "Версія {$i}: Ми створюємо платформу для підтримки українських митців.",
                        'Ваші донати допомагають розвивати сучасне мистецтво в Україні.',
                        'Разом ми будуємо культурне майбутнє нашої країни.',
                    ],
                    'en' => [
                        "Version {$i}: We create a platform to support Ukrainian artists.",
                        'Your donations help develop contemporary art in Ukraine.',
                        'Together we build the cultural future of our country.',
                    ],
                ],

                'total_collected' => rand(500000, 5000000),
                'declared_projects' => rand(100, 1000),
                'active_projects' => rand(50, 500),
                'completed_projects' => rand(200, 2000),
                'sold_projects' => rand(10, 200),

                'partners_title' => [
                    'ua' => 'Наші партнери',
                    'en' => 'Our Partners',
                ],

                'ad_first_title' => [
                    'ua' => "Долучайся до перемоги через мистецтво! Версія {$i}",
                    'en' => "Join the victory through art! Version {$i}",
                ],
                'ad_first_button_text' => [
                    'ua' => 'Підтримати зараз',
                    'en' => 'Support Now',
                ],
                'ad_first_image' => "advertising/ad-first-{$i}.webp",

                'ad_second_title' => [
                    'ua' => "Твоя допомога створює майбутнє! Тест {$i}",
                    'en' => "Your help creates the future! Test {$i}",
                ],
                'ad_second_button_text' => [
                    'ua' => 'Допомогти митцям',
                    'en' => 'Help Artists',
                ],
                'ad_second_image' => "advertising/ad-second-{$i}.webp",

                'footer_expert_title' => [
                    'ua' => 'Експерти, приєднуйтесь до нас!',
                    'en' => 'Experts, join us!',
                ],
                'footer_expert_text' => [
                    'ua' => "Версія {$i}: Фонд ID_Art UA шукає професіоналів для співпраці...",
                    'en' => "Version {$i}: ID_Art UA Foundation is looking for professionals for cooperation...",
                ],
                'footer_expert_features' => [
                    'ua' => [
                        "Розвиток сучасного українського мистецтва версії {$i}",
                        'Участь у міжнародних виставках та заходах',
                        'Промоція українських талантів по всьому світу',
                    ],
                    'en' => [
                        "Development of contemporary Ukrainian art version {$i}",
                        'Participation in international exhibitions and events',
                        'Promotion of Ukrainian talents worldwide',
                    ],
                ],
                'footer_expert_button_text' => [
                    'ua' => 'Подати заявку',
                    'en' => 'Apply Now',
                ],

                'is_active' => $isActive,
            ]);

            $this->info("Создана запись #{$i}".($isActive ? ' (активная)' : ''));
        }

        $this->success("Успешно создано {$count} тестовых записей!");
        $this->info('Первая запись установлена как активная.');

        return self::SUCCESS;
    }
}
