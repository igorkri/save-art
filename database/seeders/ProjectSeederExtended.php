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

class ProjectSeederExtended extends Seeder
{
    /**
     * Створює розширені проєкти з детальною інформацією
     */
    public function run(): void
    {
        // Отримуємо митців
        $oksana = User::where('email', 'oksana.petrenko@example.com')->first();
        $taras = User::where('email', 'taras.kovalenko@example.com')->first();
        $maria = User::where('email', 'maria.shevchenko@example.com')->first();
        $dmytro = User::where('email', 'dmytro.lytvyn@example.com')->first();
        $anna = User::where('email', 'anna.pavlenko@example.com')->first();

        $this->createProject1($oksana);
        $this->createProject2($taras);
        $this->createProject3($maria);
        $this->createProject4($dmytro);
        $this->createProject5($anna);

        // Додаткові проєкти
        $this->createProject6($oksana);
        $this->createProject7($taras);
        $this->createProject8($maria);
        $this->createProject9($dmytro);
        $this->createProject10($anna);
    }

    private function createProject1($user): void
    {
        if (! $user || Project::where('slug', 'vystavka-suchasnoho-zhyvopysu')->exists()) {
            return;
        }

        $project = Project::create([
            'user_id' => $user->id,
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
                'uk' => ['живопис', 'виставка', 'українське мистецтво', 'сучасне мистецтво', 'патріотизм'],
                'en' => ['painting', 'exhibition', 'Ukrainian art', 'contemporary art', 'patriotism'],
            ],
            'art_category' => ArtCategory::FineArt,
            'art_subcategory' => 'painting',
            'currency' => Currency::UAH,
            'budget_goal' => 150000,
            'budget_collected' => 87500,
            'estimated_days' => 90,
            'characteristics' => [
                'uk' => [
                    'Кількість робіт' => '30 картин',
                    'Формат' => 'Персональна виставка',
                    'Тривалість' => '2 тижні',
                    'Локація' => 'Галерея "Мистецький Арсенал", Київ',
                ],
                'en' => [
                    'Number of works' => '30 paintings',
                    'Format' => 'Personal exhibition',
                    'Duration' => '2 weeks',
                    'Location' => 'Gallery "Art Arsenal", Kyiv',
                ],
            ],
            'budget_items' => [
                ['name' => ['uk' => 'Оренда галереї', 'en' => 'Gallery rental'], 'amount' => 50000],
                ['name' => ['uk' => 'Матеріали для картин', 'en' => 'Painting materials'], 'amount' => 40000],
                ['name' => ['uk' => 'Поліграфія та реклама', 'en' => 'Printing and advertising'], 'amount' => 30000],
                ['name' => ['uk' => 'Технічне обладнання', 'en' => 'Technical equipment'], 'amount' => 20000],
                ['name' => ['uk' => 'Кейтеринг на відкритті', 'en' => 'Catering at opening'], 'amount' => 10000],
            ],
            'additional_info' => [
                'uk' => 'Виставка включає роботи в різних техніках: олія, акрил, акварель. Буде організовано майстер-класи та зустрічі з художниками. Частина коштів від продажу картин піде на підтримку ЗСУ.',
                'en' => 'The exhibition includes works in various techniques: oil, acrylic, watercolor. Master classes and meetings with artists will be organized. Part of the funds from the sale of paintings will go to support the Armed Forces.',
            ],
            'announced_at' => now()->subDays(15),
            'planned_completion_at' => now()->addDays(75),
        ]);

        $this->createStages($project, [
            [
                'order' => 1,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Підготовка робіт', 'en' => 'Preparation of works'],
                'description' => ['uk' => 'Створення 30 нових картин, підготовка рам та паспарту', 'en' => 'Creating 30 new paintings, preparing frames and mats'],
                'budget_planned' => 50000,
                'budget_actual' => 32000,
                'days_planned' => 30,
                'started_at' => now()->subDays(15),
            ],
            [
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Оренда та монтаж', 'en' => 'Rental and installation'],
                'description' => ['uk' => 'Оренда галереї, монтаж експозиції, встановлення освітлення та інформаційних табличок', 'en' => 'Gallery rental, exposition installation, lighting and information plates setup'],
                'budget_planned' => 70000,
                'days_planned' => 45,
            ],
            [
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Відкриття та проведення', 'en' => 'Opening and event'],
                'description' => ['uk' => 'Урочисте відкриття з прес-конференцією, організація екскурсій та майстер-класів', 'en' => 'Grand opening with press conference, organizing tours and master classes'],
                'budget_planned' => 30000,
                'days_planned' => 15,
            ],
        ]);

        $this->createBonuses($project, [
            [
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Подяка на виставці', 'en' => 'Thank you at exhibition'],
                'description' => ['uk' => 'Ваше ім\'я на стенді подяк меценатам', 'en' => 'Your name on the patron thank you board'],
                'quantity' => null,
            ],
            [
                'order' => 2,
                'min_donation' => 2000,
                'title' => ['uk' => 'Запрошення на відкриття', 'en' => 'Opening invitation'],
                'description' => ['uk' => 'Персональне запрошення на урочисте відкриття виставки з+ коктейлем', 'en' => 'Personal invitation to the grand opening with cocktail'],
                'quantity' => 50,
                'quantity_claimed' => 23,
            ],
            [
                'order' => 3,
                'min_donation' => 5000,
                'title' => ['uk' => 'Екскурсія з художницею', 'en' => 'Tour with the artist'],
                'description' => ['uk' => 'Приватна екскурсія виставкою разом з художницею', 'en' => 'Private tour of the exhibition with the artist'],
                'quantity' => 20,
                'quantity_claimed' => 12,
            ],
            [
                'order' => 4,
                'min_donation' => 10000,
                'title' => ['uk' => 'Авторський принт', 'en' => 'Author\'s print'],
                'description' => ['uk' => 'Підписаний художницею принт однієї з картин виставки (A3)', 'en' => 'Artist-signed print of one exhibition painting (A3)'],
                'quantity' => 15,
                'quantity_claimed' => 8,
            ],
            [
                'order' => 5,
                'min_donation' => 25000,
                'title' => ['uk' => 'Мала картина', 'en' => 'Small painting'],
                'description' => ['uk' => 'Оригінальна авторська картина (30x40 см)', 'en' => 'Original author\'s painting (30x40 cm)'],
                'quantity' => 5,
                'quantity_claimed' => 3,
            ],
        ]);
    }

    private function createProject2($user): void
    {
        if (! $user || Project::where('slug', 'pamiatnyk-heroiam-ukrainy')->exists()) {
            return;
        }

        $project = Project::create([
            'user_id' => $user->id,
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
                'uk' => ['скульптура', 'монумент', 'бронза', 'патріотизм', 'герої'],
                'en' => ['sculpture', 'monument', 'bronze', 'patriotism', 'heroes'],
            ],
            'art_category' => ArtCategory::FineArt,
            'art_subcategory' => 'sculpture',
            'currency' => Currency::UAH,
            'budget_goal' => 500000,
            'budget_collected' => 325000,
            'estimated_days' => 180,
            'characteristics' => [
                'uk' => [
                    'Висота' => '3.5 метра',
                    'Матеріал' => 'Бронза на гранітному п\'єдесталі',
                    'Вага' => 'Близько 2 тонн',
                    'Місце встановлення' => 'Центральний парк',
                ],
                'en' => [
                    'Height' => '3.5 meters',
                    'Material' => 'Bronze on granite pedestal',
                    'Weight' => 'About 2 tons',
                    'Installation location' => 'Central Park',
                ],
            ],
            'budget_items' => [
                ['name' => ['uk' => 'Бронзове лиття', 'en' => 'Bronze casting'], 'amount' => 250000],
                ['name' => ['uk' => 'Гранітний п\'єдестал', 'en' => 'Granite pedestal'], 'amount' => 80000],
                ['name' => ['uk' => 'Робота скульптора', 'en' => 'Sculptor\'s work'], 'amount' => 80000],
                ['name' => ['uk' => 'Фундамент та монтаж', 'en' => 'Foundation and installation'], 'amount' => 60000],
                ['name' => ['uk' => 'Благоустрій території', 'en' => 'Landscaping'], 'amount' => 30000],
            ],
            'additional_info' => [
                'uk' => 'Скульптура буде доповнена меморіальною дошкою з іменами героїв та QR-кодом з їх історіями. Проєкт узгоджено з міською владою.',
                'en' => 'The sculpture will be complemented by a memorial plaque with the names of heroes and a QR code with their stories. The project is agreed with the city authorities.',
            ],
            'announced_at' => now()->subDays(90),
            'planned_completion_at' => now()->addDays(90),
        ]);

        $this->createStages($project, [
            [
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Ескізи та модель', 'en' => 'Sketches and model'],
                'description' => ['uk' => 'Розробка концепції, створення ескізів та макету скульптури 1:10', 'en' => 'Concept development, creating sketches and 1:10 scale model'],
                'budget_planned' => 80000,
                'budget_actual' => 75000,
                'days_planned' => 45,
                'started_at' => now()->subDays(90),
                'completed_at' => now()->subDays(45),
            ],
            [
                'order' => 2,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Лиття скульптури', 'en' => 'Sculpture casting'],
                'description' => ['uk' => 'Виготовлення форм, лиття бронзи, обробка та патинування', 'en' => 'Mold making, bronze casting, processing and patination'],
                'budget_planned' => 320000,
                'budget_actual' => 280000,
                'days_planned' => 90,
                'started_at' => now()->subDays(45),
            ],
            [
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Фундамент', 'en' => 'Foundation'],
                'description' => ['uk' => 'Підготовка місця, заливка фундаменту, монтаж п\'єдесталу', 'en' => 'Site preparation, foundation pouring, pedestal installation'],
                'budget_planned' => 50000,
                'days_planned' => 20,
            ],
            [
                'order' => 4,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Встановлення та відкриття', 'en' => 'Installation and opening'],
                'description' => ['uk' => 'Транспортування, монтаж скульптури, благоустрій, урочисте відкриття', 'en' => 'Transportation, sculpture installation, landscaping, grand opening'],
                'budget_planned' => 50000,
                'days_planned' => 25,
            ],
        ]);

        $this->createBonuses($project, [
            [
                'order' => 1,
                'min_donation' => 1000,
                'title' => ['uk' => 'Ім\'я на меморіалі', 'en' => 'Name on memorial'],
                'description' => ['uk' => 'Ваше ім\'я буде викарбувано на меморіальній дошці меценатів', 'en' => 'Your name will be engraved on the patron memorial plaque'],
                'quantity' => 100,
                'quantity_claimed' => 47,
            ],
            [
                'order' => 2,
                'min_donation' => 5000,
                'title' => ['uk' => 'Міні-репліка', 'en' => 'Mini replica'],
                'description' => ['uk' => 'Бронзова міні-репліка пам\'ятника (15 см) з нумерацією', 'en' => 'Bronze mini-replica of monument (15 cm) numbered'],
                'quantity' => 20,
                'quantity_claimed' => 12,
            ],
            [
                'order' => 3,
                'min_donation' => 15000,
                'title' => ['uk' => 'Запрошення на відкриття', 'en' => 'Opening invitation'],
                'description' => ['uk' => 'VIP-запрошення на урочисте відкриття пам\'ятника', 'en' => 'VIP invitation to the monument grand opening'],
                'quantity' => 30,
                'quantity_claimed' => 18,
            ],
        ]);
    }

    // Створюємо допоміжні методи
    private function createStages($project, array $stages): void
    {
        foreach ($stages as $stageData) {
            ProjectStage::create(array_merge(['project_id' => $project->id], $stageData));
        }
    }

    private function createBonuses($project, array $bonuses): void
    {
        foreach ($bonuses as $bonusData) {
            ProjectBonus::create(array_merge(['project_id' => $project->id], $bonusData));
        }
    }

    // Додаткові методи для проєктів 3-10 будуть додані...
    private function createProject3($user): void
    {
        // Театральна постановка (вже існуючий код)
    }

    private function createProject4($user): void
    {
        // Музичний альбом (вже існуючий код)
    }

    private function createProject5($user): void
    {
        // Фотопроєкт (вже існуючий код)
    }

    private function createProject6($user): void
    {
        // Новий проєкт 6
    }

    private function createProject7($user): void
    {
        // Новий проєкт 7
    }

    private function createProject8($user): void
    {
        // Новий проєкт 8
    }

    private function createProject9($user): void
    {
        // Новий проєкт 9
    }

    private function createProject10($user): void
    {
        // Новий проєкт 10
    }
}
