<?php

namespace Database\Seeders;

use App\Enums\ArtCategory;
use App\Enums\Currency;
use App\Enums\UserType;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Models\ProjectStage;
use App\Models\User;
use Database\Seeders\Helpers\ImageSeederHelper;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Отримуємо митців
        $oksana = User::where('email', 'oksana.petrenko@example.com')->first();
        $taras = User::where('email', 'taras.kovalenko@example.com')->first();
        $maria = User::where('email', 'maria.shevchenko@example.com')->first();
        $dmytro = User::where('email', 'dmytro.lytvyn@example.com')->first();
        $anna = User::where('email', 'anna.pavlenko@example.com')->first();

        // Проєкт 1: Виставка сучасного українського живопису (Оксана) - Оголошений
        if ($oksana && ! Project::where('slug', 'vystavka-suchasnoho-zhyvopysu')->exists()) {
            $project = Project::create([
                'user_id' => $oksana->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Announced,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'vystavka-suchasnoho-zhyvopysu',
                'title' => [
                    'uk' => 'Виставка сучасного українського живопису "Незламність"',
                    'en' => 'Exhibition of Contemporary Ukrainian Painting "Unbreakable"',
                ],
                'short_description' => [
                    'uk' => 'Мистецька виставка, присвячена силі духу українського народу. 30 нових робіт від провідних художників України.',
                    'en' => 'An art exhibition dedicated to the strength of the Ukrainian people\'s spirit. 30 new works from leading artists of Ukraine.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('painting'),
                'tags' => [
                    'uk' => ['живопис', 'виставка', 'українське мистецтво', 'сучасне мистецтво'],
                    'en' => ['painting', 'exhibition', 'Ukrainian art', 'contemporary art'],
                ],
                'art_category' => ArtCategory::FineArt,
                'art_subcategory' => 'painting',
                'currency' => Currency::UAH,
                'budget_goal' => 150000,
                'budget_collected' => 87500,
                'estimated_days' => 90,
                'announced_at' => now()->subDays(15),
                'planned_completion_at' => now()->addDays(75),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Підготовка робіт', 'en' => 'Preparation of works'],
                'description' => ['uk' => 'Створення нових картин та підготовка існуючих робіт до виставки', 'en' => 'Creating new paintings and preparing existing works for the exhibition'],
                'budget_planned' => 50000,
                'days_planned' => 30,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Оренда приміщення та монтаж', 'en' => 'Venue rental and installation'],
                'description' => ['uk' => 'Оренда галереї, монтаж експозиції, освітлення', 'en' => 'Gallery rental, exposition setup, lighting'],
                'budget_planned' => 70000,
                'days_planned' => 45,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Відкриття та проведення', 'en' => 'Opening and event'],
                'description' => ['uk' => 'Урочисте відкриття, прес-тур, екскурсії', 'en' => 'Grand opening, press tour, guided tours'],
                'budget_planned' => 30000,
                'days_planned' => 15,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Подяка на виставці', 'en' => 'Thank you at the exhibition'],
                'description' => ['uk' => 'Ваше ім\'я на стенді подяк меценатам', 'en' => 'Your name on the patron thank you board'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 2000,
                'title' => ['uk' => 'Запрошення на відкриття', 'en' => 'Opening invitation'],
                'description' => ['uk' => 'Персональне запрошення на урочисте відкриття виставки', 'en' => 'Personal invitation to the grand opening of the exhibition'],
                'quantity' => 50,
                'quantity_claimed' => 23,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 10000,
                'title' => ['uk' => 'Авторський принт', 'en' => 'Author\'s print'],
                'description' => ['uk' => 'Підписаний художницею принт однієї з картин виставки', 'en' => 'Artist-signed print of one of the exhibition paintings'],
                'quantity' => 15,
                'quantity_claimed' => 8,
            ]);
        }

        // Проєкт 2: Пам'ятник героям (Тарас) - В процесі реалізації
        if ($taras && ! Project::where('slug', 'pamiatnyk-heroiam-ukrainy')->exists()) {
            $project = Project::create([
                'user_id' => $taras->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::InProgress,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'pamiatnyk-heroiam-ukrainy',
                'title' => [
                    'uk' => 'Пам\'ятник героям України',
                    'en' => 'Monument to Ukrainian Heroes',
                ],
                'short_description' => [
                    'uk' => 'Монументальна бронзова скульптура присвячена захисникам України. Буде встановлена у центральному парку міста.',
                    'en' => 'A monumental bronze sculpture dedicated to Ukrainian defenders. Will be installed in the central city park.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('sculpture'),
                'tags' => [
                    'uk' => ['скульптура', 'монумент', 'бронза', 'патріотизм'],
                    'en' => ['sculpture', 'monument', 'bronze', 'patriotism'],
                ],
                'art_category' => ArtCategory::FineArt,
                'art_subcategory' => 'sculpture',
                'currency' => Currency::UAH,
                'budget_goal' => 500000,
                'budget_collected' => 325000,
                'estimated_days' => 180,
                'announced_at' => now()->subDays(90),
                'planned_completion_at' => now()->addDays(90),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Створення ескізів та моделі', 'en' => 'Creating sketches and model'],
                'description' => ['uk' => 'Розробка концепції, створення ескізів та макету скульптури в масштабі 1:10', 'en' => 'Concept development, creating sketches and a 1:10 scale model'],
                'budget_planned' => 80000,
                'budget_actual' => 75000,
                'days_planned' => 45,
                'started_at' => now()->subDays(90),
                'completed_at' => now()->subDays(45),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Виготовлення скульптури', 'en' => 'Sculpture production'],
                'description' => ['uk' => 'Лиття бронзової скульптури та обробка деталей', 'en' => 'Bronze sculpture casting and detail processing'],
                'budget_planned' => 320000,
                'days_planned' => 90,
                'started_at' => now()->subDays(45),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Встановлення', 'en' => 'Installation'],
                'description' => ['uk' => 'Підготовка фундаменту, транспортування та монтаж скульптури', 'en' => 'Foundation preparation, transportation and sculpture installation'],
                'budget_planned' => 100000,
                'days_planned' => 45,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 1000,
                'title' => ['uk' => 'Ім\'я на меморіалі', 'en' => 'Name on memorial'],
                'description' => ['uk' => 'Ваше ім\'я буде викарбувано на меморіальній дошці', 'en' => 'Your name will be engraved on the memorial plaque'],
                'quantity' => 100,
                'quantity_claimed' => 47,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 5000,
                'title' => ['uk' => 'Міні-репліка скульптури', 'en' => 'Mini sculpture replica'],
                'description' => ['uk' => 'Бронзова міні-репліка пам\'ятника (15 см)', 'en' => 'Bronze mini-replica of the monument (15 cm)'],
                'quantity' => 20,
                'quantity_claimed' => 12,
            ]);
        }

        // Проєкт 3: Театральна постановка (Марія) - Завершений
        if ($maria && ! Project::where('slug', 'teatralna-postanovka-revoliutsiia')->exists()) {
            $project = Project::create([
                'user_id' => $maria->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Completed,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'teatralna-postanovka-revoliutsiia',
                'title' => [
                    'uk' => 'Театральна постановка "Майдан: Голоси свободи"',
                    'en' => 'Theatre Production "Maidan: Voices of Freedom"',
                ],
                'short_description' => [
                    'uk' => 'Документальна театральна постановка про події Революції Гідності. Базується на реальних свідченнях учасників.',
                    'en' => 'Documentary theatre production about the Revolution of Dignity. Based on real testimonies of participants.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('theatre'),
                'tags' => [
                    'uk' => ['театр', 'документальна драма', 'історія', 'сучасність'],
                    'en' => ['theatre', 'documentary drama', 'history', 'modernity'],
                ],
                'art_category' => ArtCategory::Scenic,
                'art_subcategory' => 'directing',
                'currency' => Currency::UAH,
                'budget_goal' => 250000,
                'budget_collected' => 250000,
                'estimated_days' => 120,
                'announced_at' => now()->subDays(150),
                'planned_completion_at' => now()->subDays(30),
                'completed_at' => now()->subDays(10),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Розробка сценарію', 'en' => 'Script development'],
                'description' => ['uk' => 'Збір свідчень, написання сценарію, робота з драматургом', 'en' => 'Collecting testimonies, script writing, work with playwright'],
                'budget_planned' => 50000,
                'budget_actual' => 48000,
                'days_planned' => 30,
                'started_at' => now()->subDays(150),
                'completed_at' => now()->subDays(120),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Кастинг та репетиції', 'en' => 'Casting and rehearsals'],
                'description' => ['uk' => 'Підбір акторів, репетиційний період', 'en' => 'Actor selection, rehearsal period'],
                'budget_planned' => 100000,
                'budget_actual' => 105000,
                'days_planned' => 60,
                'started_at' => now()->subDays(120),
                'completed_at' => now()->subDays(60),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Прем\'єра та покази', 'en' => 'Premiere and performances'],
                'description' => ['uk' => 'Сценографія, костюми, світло, звук, 10 вистав', 'en' => 'Set design, costumes, lighting, sound, 10 performances'],
                'budget_planned' => 100000,
                'budget_actual' => 97000,
                'days_planned' => 30,
                'started_at' => now()->subDays(60),
                'completed_at' => now()->subDays(10),
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 300,
                'title' => ['uk' => 'Квиток на виставу', 'en' => 'Performance ticket'],
                'description' => ['uk' => 'Один квиток на будь-яку дату вистави', 'en' => 'One ticket to any performance date'],
                'quantity' => 200,
                'quantity_claimed' => 200,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 1500,
                'title' => ['uk' => 'VIP-місця на прем\'єрі', 'en' => 'VIP seats at premiere'],
                'description' => ['uk' => '2 VIP-місця на прем\'єрі з зустріччю з акторами', 'en' => '2 VIP seats at the premiere with meet & greet with actors'],
                'quantity' => 30,
                'quantity_claimed' => 30,
            ]);
        }

        // Проєкт 4: Музичний альбом (Дмитро) - Оголошений
        if ($dmytro && ! Project::where('slug', 'muzychnyi-albom-koreni-ta-kryla')->exists()) {
            $project = Project::create([
                'user_id' => $dmytro->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Announced,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'muzychnyi-albom-koreni-ta-kryla',
                'title' => [
                    'uk' => 'Музичний альбом "Корені та крила"',
                    'en' => 'Music Album "Roots and Wings"',
                ],
                'short_description' => [
                    'uk' => 'Альбом із 12 українських народних пісень у сучасній обробці. Поєднання автентичності та актуального звучання.',
                    'en' => 'Album with 12 Ukrainian folk songs in contemporary arrangements. Combination of authenticity and modern sound.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('music'),
                'tags' => [
                    'uk' => ['музика', 'фолк', 'електроніка', 'альбом'],
                    'en' => ['music', 'folk', 'electronic', 'album'],
                ],
                'art_category' => ArtCategory::Music,
                'art_subcategory' => null,
                'currency' => Currency::UAH,
                'budget_goal' => 180000,
                'budget_collected' => 42000,
                'estimated_days' => 150,
                'announced_at' => now()->subDays(7),
                'planned_completion_at' => now()->addDays(143),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Аранжування', 'en' => 'Arrangements'],
                'description' => ['uk' => 'Створення аранжувань, запис демо-версій', 'en' => 'Creating arrangements, recording demos'],
                'budget_planned' => 40000,
                'days_planned' => 45,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Студійний запис', 'en' => 'Studio recording'],
                'description' => ['uk' => 'Оренда студії, запис всіх інструментів та вокалу', 'en' => 'Studio rental, recording all instruments and vocals'],
                'budget_planned' => 80000,
                'days_planned' => 60,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Мікс, мастерінг та реліз', 'en' => 'Mix, mastering and release'],
                'description' => ['uk' => 'Підсумкове зведення, мастерінг, дизайн обкладинки, публікація', 'en' => 'Final mixing, mastering, cover design, publication'],
                'budget_planned' => 60000,
                'days_planned' => 45,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 200,
                'title' => ['uk' => 'Цифрова копія альбому', 'en' => 'Digital album copy'],
                'description' => ['uk' => 'Завантаження альбому у форматі FLAC після релізу', 'en' => 'Album download in FLAC format after release'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 800,
                'title' => ['uk' => 'Вінілова платівка', 'en' => 'Vinyl record'],
                'description' => ['uk' => 'Підписана вінілова платівка з обмеженого тиражу', 'en' => 'Signed vinyl record from limited edition'],
                'quantity' => 100,
                'quantity_claimed' => 15,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 3000,
                'title' => ['uk' => 'Приватний концерт', 'en' => 'Private concert'],
                'description' => ['uk' => 'Камерний концерт для вас та ваших гостей (до 20 осіб)', 'en' => 'Chamber concert for you and your guests (up to 20 people)'],
                'quantity' => 5,
                'quantity_claimed' => 1,
            ]);
        }

        // Проєкт 5: Фотопроєкт (Анна) - Чернетка
        if ($anna && ! Project::where('slug', 'fotoproekt-oblychchia-nezlamnykh')->exists()) {
            $project = Project::create([
                'user_id' => $anna->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Draft,
                'status_moderation' => \App\Enums\ModerationStatus::Pending,
                'slug' => 'fotoproekt-oblychchia-nezlamnykh',
                'title' => [
                    'uk' => 'Фотопроєкт "Обличчя незламних"',
                    'en' => 'Photo Project "Faces of the Unbreakable"',
                ],
                'short_description' => [
                    'uk' => 'Серія портретів українців, які продовжують творити, працювати і жити попри всі виклики. 100 історій мужності.',
                    'en' => 'A series of portraits of Ukrainians who continue to create, work and live despite all challenges. 100 stories of courage.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('photography'),
                'tags' => [
                    'uk' => ['фотографія', 'портрет', 'документалістика', 'люди'],
                    'en' => ['photography', 'portrait', 'documentary', 'people'],
                ],
                'art_category' => ArtCategory::Visual,
                'art_subcategory' => 'photography',
                'currency' => Currency::UAH,
                'budget_goal' => 120000,
                'budget_collected' => 0,
                'estimated_days' => 200,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Пошук героїв', 'en' => 'Finding heroes'],
                'description' => ['uk' => 'Пошук та відбір учасників проєкту з різних регіонів', 'en' => 'Search and selection of project participants from different regions'],
                'budget_planned' => 20000,
                'days_planned' => 40,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Фотозйомки', 'en' => 'Photo shoots'],
                'description' => ['uk' => 'Поїздки регіонами, проведення фотосесій і інтерв\'ю', 'en' => 'Regional trips, conducting photo sessions and interviews'],
                'budget_planned' => 60000,
                'days_planned' => 120,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Виставка та книга', 'en' => 'Exhibition and book'],
                'description' => ['uk' => 'Обробка фото, друк книги, організація виставки', 'en' => 'Photo processing, book printing, exhibition organization'],
                'budget_planned' => 40000,
                'days_planned' => 40,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Електронна книга', 'en' => 'E-book'],
                'description' => ['uk' => 'PDF-версія фотокниги з усіма історіями', 'en' => 'PDF version of the photo book with all stories'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 1500,
                'title' => ['uk' => 'Друкована книга', 'en' => 'Printed book'],
                'description' => ['uk' => 'Підписаний примірник друкованої фотокниги', 'en' => 'Signed copy of printed photo book'],
                'quantity' => 200,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 5000,
                'title' => ['uk' => 'Авторський відбиток', 'en' => 'Author\'s print'],
                'description' => ['uk' => 'Велий підписаний відбиток однієї з фотографій (40x60 см)', 'en' => 'Large signed print of one of the photographs (40x60 cm)'],
                'quantity' => 30,
                'quantity_claimed' => 0,
            ]);
        }
    }
}
