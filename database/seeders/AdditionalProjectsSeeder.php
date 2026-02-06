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

class AdditionalProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $oksana = User::where('email', 'oksana.petrenko@example.com')->first();
        $taras = User::where('email', 'taras.kovalenko@example.com')->first();
        $maria = User::where('email', 'maria.shevchenko@example.com')->first();
        $dmytro = User::where('email', 'dmytro.lytvyn@example.com')->first();
        $anna = User::where('email', 'anna.pavlenko@example.com')->first();

        // Проєкт 6: Графічний роман про Україну
        if ($anna && ! Project::where('slug', 'graphic-novel-ukraine')->exists()) {
            $project = Project::create([
                'user_id' => $anna->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Announced,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'graphic-novel-ukraine',
                'title' => [
                    'uk' => 'Графічний роман "Голоси Майдану"',
                    'en' => 'Graphic Novel "Voices of Maidan"',
                ],
                'short_description' => [
                    'uk' => 'Унікальний графічний роман про події Революції Гідності з точку зору простих людей. 200 сторінок історії та мужності.',
                    'en' => 'A unique graphic novel about the Revolution of Dignity from the perspective of ordinary people. 200 pages of history and courage.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('art'),
                'tags' => [
                    'uk' => ['графічний роман', 'комікс', 'історія', 'революція гідності', 'мистецтво'],
                    'en' => ['graphic novel', 'comics', 'history', 'revolution of dignity', 'art'],
                ],
                'art_category' => ArtCategory::Literature,
                'art_subcategory' => 'prose',
                'currency' => Currency::UAH,
                'budget_goal' => 200000,
                'budget_collected' => 45000,
                'estimated_days' => 150,
                'characteristics' => [
                    'uk' => [
                        'Формат' => 'A4, тверда обкладинка',
                        'Кількість сторінок' => '200',
                        'Мова' => 'Українська та англійська',
                        'Тираж' => '2000 примірників',
                    ],
                    'en' => [
                        'Format' => 'A4, hardcover',
                        'Number of pages' => '200',
                        'Language' => 'Ukrainian and English',
                        'Print run' => '2000 copies',
                    ],
                ],
                'budget_items' => [
                    ['name' => ['uk' => 'Робота ілюстратора', 'en' => 'Illustrator work'], 'amount' => 80000],
                    ['name' => ['uk' => 'Робота письменника', 'en' => 'Writer work'], 'amount' => 40000],
                    ['name' => ['uk' => 'Друк тиражу', 'en' => 'Print run'], 'amount' => 50000],
                    ['name' => ['uk' => 'Редагування та верстка', 'en' => 'Editing and layout'], 'amount' => 20000],
                    ['name' => ['uk' => 'Маркетинг та промоція', 'en' => 'Marketing and promotion'], 'amount' => 10000],
                ],
                'additional_info' => [
                    'uk' => 'Роман базується на реальних інтерв\'ю з учасниками подій. Включає документальні фотографії та спогади. Буде презентований на міжнародних книжкових ярмарках.',
                    'en' => 'The novel is based on real interviews with event participants. Includes documentary photos and memories. Will be presented at international book fairs.',
                ],
                'announced_at' => now()->subDays(20),
                'planned_completion_at' => now()->addDays(130),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Збір матеріалів', 'en' => 'Material collection'],
                'description' => ['uk' => 'Інтерв\'ю з учасниками, робота з архівами, збір фотографій', 'en' => 'Interviews with participants, archive work, photo collection'],
                'budget_planned' => 20000,
                'budget_actual' => 15000,
                'days_planned' => 40,
                'started_at' => now()->subDays(20),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Написання сценарію', 'en' => 'Script writing'],
                'description' => ['uk' => 'Створення тексту, розподіл по панелях, діалоги', 'en' => 'Text creation, panel distribution, dialogues'],
                'budget_planned' => 40000,
                'days_planned' => 50,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Ілюстрування', 'en' => 'Illustration'],
                'description' => ['uk' => 'Малювання всіх 200 сторінок, колоризація', 'en' => 'Drawing all 200 pages, colorization'],
                'budget_planned' => 80000,
                'days_planned' => 80,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 4,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Видання', 'en' => 'Publishing'],
                'description' => ['uk' => 'Редагування, верстка, друк, презентація', 'en' => 'Editing, layout, printing, presentation'],
                'budget_planned' => 60000,
                'days_planned' => 30,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 400,
                'title' => ['uk' => 'Електронна версія', 'en' => 'Digital version'],
                'description' => ['uk' => 'PDF-версія графічного роману', 'en' => 'PDF version of the graphic novel'],
                'quantity' => null,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 1000,
                'title' => ['uk' => 'Друкований примірник', 'en' => 'Printed copy'],
                'description' => ['uk' => 'Підписаний авторами примірник книги з твердою обкладинкою', 'en' => 'Signed by authors hardcover copy'],
                'quantity' => 500,
                'quantity_claimed' => 35,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 3000,
                'title' => ['uk' => 'Колекційне видання', 'en' => 'Collector\'s edition'],
                'description' => ['uk' => 'Нумероване видання з авторським малюнком та ексклюзивним артбуком', 'en' => 'Numbered edition with author\'s drawing and exclusive artbook'],
                'quantity' => 50,
                'quantity_claimed' => 12,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 4,
                'min_donation' => 10000,
                'title' => ['uk' => 'Оригінальна ілюстрація', 'en' => 'Original illustration'],
                'description' => ['uk' => 'Оригінальний малюнок з книги (олівець та туш)', 'en' => 'Original drawing from the book (pencil and ink)'],
                'quantity' => 10,
                'quantity_claimed' => 3,
            ]);
        }

        // Проєкт 7: Документальний фільм
        if ($maria && ! Project::where('slug', 'documentary-film-artists')->exists()) {
            $project = Project::create([
                'user_id' => $maria->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::InProgress,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'documentary-film-artists',
                'title' => [
                    'uk' => 'Документальний фільм "Митці на передовій"',
                    'en' => 'Documentary Film "Artists on the Frontline"',
                ],
                'short_description' => [
                    'uk' => 'Фільм розповідає про українських художників, музикантів та письменників, які продовжують творити в умовах війни.',
                    'en' => 'The film tells about Ukrainian artists, musicians and writers who continue to create during the war.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('cinema'),
                'tags' => [
                    'uk' => ['документальний фільм', 'митці', 'війна', 'культура', 'мистецтво'],
                    'en' => ['documentary', 'artists', 'war', 'culture', 'art'],
                ],
                'art_category' => ArtCategory::Visual,
                'art_subcategory' => 'cinema',
                'currency' => Currency::UAH,
                'budget_goal' => 800000,
                'budget_collected' => 520000,
                'estimated_days' => 240,
                'characteristics' => [
                    'uk' => [
                        'Тривалість' => '90 хвилин',
                        'Формат' => '4K UHD',
                        'Мова' => 'Українська з англійськими субтитрами',
                        'Герої' => '15 митців з різних сфер',
                    ],
                    'en' => [
                        'Duration' => '90 minutes',
                        'Format' => '4K UHD',
                        'Language' => 'Ukrainian with English subtitles',
                        'Characters' => '15 artists from different fields',
                    ],
                ],
                'budget_items' => [
                    ['name' => ['uk' => 'Зйомка (обладнання та група)', 'en' => 'Filming (equipment and crew)'], 'amount' => 300000],
                    ['name' => ['uk' => 'Постпродакшн', 'en' => 'Post-production'], 'amount' => 200000],
                    ['name' => ['uk' => 'Подорожі для зйомок', 'en' => 'Travel for filming'], 'amount' => 150000],
                    ['name' => ['uk' => 'Музика та звук', 'en' => 'Music and sound'], 'amount' => 100000],
                    ['name' => ['uk' => 'Промоція та дистрибуція', 'en' => 'Promotion and distribution'], 'amount' => 50000],
                ],
                'additional_info' => [
                    'uk' => 'Фільм буде представлений на міжнародних кінофестивалях. Планується показ на національному телебаченні та стрімінгових платформах. Частина прибутку піде на підтримку митців.',
                    'en' => 'The film will be presented at international film festivals. Screening on national television and streaming platforms is planned. Part of the profit will go to support artists.',
                ],
                'announced_at' => now()->subDays(120),
                'planned_completion_at' => now()->addDays(120),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Пре-продакшн', 'en' => 'Pre-production'],
                'description' => ['uk' => 'Розробка концепції, підбір героїв, планування зйомок', 'en' => 'Concept development, character selection, filming planning'],
                'budget_planned' => 50000,
                'budget_actual' => 48000,
                'days_planned' => 45,
                'started_at' => now()->subDays(120),
                'completed_at' => now()->subDays(75),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Зйомки', 'en' => 'Filming'],
                'description' => ['uk' => 'Зйомки інтерв\'ю та documentary footage в різних локаціях', 'en' => 'Filming interviews and documentary footage in various locations'],
                'budget_planned' => 450000,
                'budget_actual' => 430000,
                'days_planned' => 90,
                'started_at' => now()->subDays(75),
                'completed_at' => now()->subDays(15),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Постпродакшн', 'en' => 'Post-production'],
                'description' => ['uk' => 'Монтаж, колоркорекція, озвучування, створення музики', 'en' => 'Editing, color correction, sound design, music creation'],
                'budget_planned' => 200000,
                'budget_actual' => 120000,
                'days_planned' => 75,
                'started_at' => now()->subDays(15),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 4,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Дистрибуція', 'en' => 'Distribution'],
                'description' => ['uk' => 'Прем\'єра, фестивалі, дистрибуція на платформах', 'en' => 'Premiere, festivals, distribution on platforms'],
                'budget_planned' => 100000,
                'days_planned' => 30,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Цифрова копія', 'en' => 'Digital copy'],
                'description' => ['uk' => 'Завантаження фільму в Full HD після релізу', 'en' => 'Film download in Full HD after release'],
                'quantity' => null,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 2000,
                'title' => ['uk' => 'Запрошення на прем\'єру', 'en' => 'Premiere invitation'],
                'description' => ['uk' => 'Особисте запрошення на прем\'єру з афтерпаті', 'en' => 'Personal invitation to premiere with afterparty'],
                'quantity' => 100,
                'quantity_claimed' => 78,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 5000,
                'title' => ['uk' => 'Blu-ray видання', 'en' => 'Blu-ray edition'],
                'description' => ['uk' => 'Колекційне Blu-ray видання з додатковими матеріалами', 'en' => 'Collector\'s Blu-ray edition with bonus materials'],
                'quantity' => 150,
                'quantity_claimed' => 92,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 4,
                'min_donation' => 20000,
                'title' => ['uk' => 'Згадка в титрах', 'en' => 'Credit mention'],
                'description' => ['uk' => 'Ваше ім\'я в титрах як виконавчого продюсера', 'en' => 'Your name in credits as executive producer'],
                'quantity' => 10,
                'quantity_claimed' => 6,
            ]);
        }
    }
}
