<?php

namespace Database\Seeders;

use App\Enums\ArtCategory;
use App\Enums\Currency;
use App\Enums\UserType;
use App\Models\ArtCategory as ArtCategoryModel;
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
                    'uk' => ['живопис', 'виставка', 'українське мистецтво', 'сучасне мистецтво', 'портрет'],
                    'en' => ['painting', 'exhibition', 'Ukrainian art', 'contemporary art', 'portrait'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::FineArt->value, 'painting'),
                'currency' => Currency::UAH,
                'budget_goal' => 150000,
                'budget_collected' => 87500,
                'estimated_days' => 90,
                'budget_items' => [
                    ['name' => ['uk' => 'Оренда галереї', 'en' => 'Gallery rental'], 'amount' => 50000],
                    ['name' => ['uk' => 'Матеріали для картин', 'en' => 'Painting materials'], 'amount' => 40000],
                    ['name' => ['uk' => 'Поліграфія та реклама', 'en' => 'Printing and advertising'], 'amount' => 30000],
                    ['name' => ['uk' => 'Технічне обладнання', 'en' => 'Technical equipment'], 'amount' => 20000],
                    ['name' => ['uk' => 'Кейтеринг на відкритті', 'en' => 'Catering at opening'], 'amount' => 10000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Концепція виставки',
                            'en' => 'Exhibition concept',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Виставка "Незламність" – це спроба осмислити через мистецтво те, що відбувається в Україні сьогодні. 30 нових робіт від провідних художників України розповідають історії мужності, надії та незламного духу нашого народу.',
                            'en' => 'The exhibition "Unbreakable" is an attempt to comprehend through art what is happening in Ukraine today. 30 new works from leading Ukrainian artists tell stories of courage, hope and the indomitable spirit of our people.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('painting'),
                        'image_alt' => [
                            'uk' => 'Одна з картин виставки "Незламність"',
                            'en' => 'One of the paintings from the "Unbreakable" exhibition',
                        ],
                        'image_caption' => [
                            'uk' => 'Експозиція включає 30 нових робіт українських художників',
                            'en' => 'The exposition includes 30 new works by Ukrainian artists',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Що ви побачите',
                            'en' => 'What you will see',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Експозиція включає роботи в різних техніках: від класичного олійного живопису до сучасних змішаних технік. Кожна картина – це особиста історія митця, його переживання та роздуми про сучасність.',
                            'en' => 'The exposition includes works in various techniques: from classical oil painting to modern mixed media. Each painting is a personal story of the artist, his experiences and reflections on modernity.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Виставка включає роботи в різних техніках: олія, акрил, акварель. Буде організовано майстер-класи та зустрічі з художниками. Частина коштів від продажу картин піде на підтримку ЗСУ.',
                    'en' => 'The exhibition includes works in various techniques: oil, acrylic, watercolor. Master classes and meetings with artists will be organized. Part of the funds from the sale of paintings will go to support the Armed Forces.',
                ],
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
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::FineArt->value, 'sculpture'),
                'currency' => Currency::UAH,
                'budget_goal' => 500000,
                'budget_collected' => 325000,
                'estimated_days' => 180,
                'budget_items' => [
                    ['name' => ['uk' => 'Створення ескізів та макету', 'en' => 'Sketches and model creation'], 'amount' => 80000],
                    ['name' => ['uk' => 'Лиття бронзи', 'en' => 'Bronze casting'], 'amount' => 250000],
                    ['name' => ['uk' => 'Обробка та патинування', 'en' => 'Processing and patination'], 'amount' => 70000],
                    ['name' => ['uk' => 'Підготовка фундаменту', 'en' => 'Foundation preparation'], 'amount' => 50000],
                    ['name' => ['uk' => 'Транспортування та монтаж', 'en' => 'Transportation and installation'], 'amount' => 50000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Ідея проєкту',
                            'en' => 'Project idea',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Монументальна скульптура "Пам\'ятник героям України" присвячена захисникам нашої країни. Це бронзова композиція висотою 3.5 метра, яка символізує незламність та силу українського народу. Скульптура зображує фігуру воїна-захисника в момент прийняття важливого рішення.',
                            'en' => 'The monumental sculpture "Monument to Ukrainian Heroes" is dedicated to the defenders of our country. This is a bronze composition 3.5 meters high, symbolizing the resilience and strength of the Ukrainian people. The sculpture depicts the figure of a warrior-defender at the moment of making an important decision.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('sculpture'),
                        'image_alt' => [
                            'uk' => 'Макет скульптури "Пам\'ятник героям України"',
                            'en' => 'Model of the sculpture "Monument to Ukrainian Heroes"',
                        ],
                        'image_caption' => [
                            'uk' => 'Бронзова скульптура висотою 3.5 метра',
                            'en' => 'Bronze sculpture 3.5 meters high',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Процес створення',
                            'en' => 'Creation process',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Робота над скульптурою включає кілька етапів: спочатку створюється ескіз та невеликий макет у масштабі 1:10, потім у натуральну величину виготовляється глиняна модель, яка служить основою для форми. Лиття бронзи – складний процес, що вимагає високої майстерності. Після лиття скульптура проходить обробку та патинування для надання благородного відтінку.',
                            'en' => 'Work on the sculpture includes several stages: first a sketch and small 1:10 scale model are created, then a full-size clay model is made, which serves as the basis for the mold. Bronze casting is a complex process requiring high skill. After casting, the sculpture undergoes processing and patination to give it a noble hue.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Скульптура буде встановлена на гранітному постаменті. Урочисте відкриття заплановано на День Незалежності. Автор проєкту - скульптор Тарас Коваленко, член Національної спілки художників України.',
                    'en' => 'The sculpture will be installed on a granite pedestal. The grand opening is planned for Independence Day. The project author is sculptor Taras Kovalenko, member of the National Union of Artists of Ukraine.',
                ],
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
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Scenic->value, 'directing'),
                'currency' => Currency::UAH,
                'budget_goal' => 250000,
                'budget_collected' => 250000,
                'estimated_days' => 120,
                'budget_items' => [
                    ['name' => ['uk' => 'Написання сценарію', 'en' => 'Script writing'], 'amount' => 50000],
                    ['name' => ['uk' => 'Гонорари акторам', 'en' => 'Actor fees'], 'amount' => 80000],
                    ['name' => ['uk' => 'Сценографія та костюми', 'en' => 'Set design and costumes'], 'amount' => 60000],
                    ['name' => ['uk' => 'Оренда театру', 'en' => 'Theatre rental'], 'amount' => 40000],
                    ['name' => ['uk' => 'Технічне забезпечення', 'en' => 'Technical support'], 'amount' => 20000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Про виставу',
                            'en' => 'About the performance',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('theatre'),
                        'image_alt' => [
                            'uk' => 'Постер театральної постановки "Майдан: Голоси свободи"',
                            'en' => 'Poster of the theatre production "Maidan: Voices of Freedom"',
                        ],
                        'image_caption' => [
                            'uk' => 'Документальна драма про Революцію Гідності',
                            'en' => 'Documentary drama about the Revolution of Dignity',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Театральна постановка "Майдан: Голоси свободи" – це документальна драма, створена на основі реальних свідчень учасників Революції Гідності. Вистава об\'єднує дванадцять акторів, кожен з яких втілює історію реальної людини, яка була на Майдані в ті переломні дні.',
                            'en' => 'The theatre production "Maidan: Voices of Freedom" is a documentary drama based on real testimonies of participants of the Revolution of Dignity. The performance brings together twelve actors, each embodying the story of a real person who was on Maidan during those pivotal days.',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Команда проєкту',
                            'en' => 'Project team',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('theatre'),
                        'image_alt' => [
                            'uk' => 'Постер театральної постановки "Майдан: Голоси свободи"',
                            'en' => 'Poster of the theatre production "Maidan: Voices of Freedom"',
                        ],
                        'image_caption' => [
                            'uk' => 'Документальна драма про Революцію Гідності',
                            'en' => 'Documentary drama about the Revolution of Dignity',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Режисер Марія Шевченко разом з драматургом провели понад 50 інтерв\'ю для створення сценарію. У виставі задіяні професійні актори Київського академічного театру драми. Сценографія і костюми створені провідними українськими художниками, які прагнули максимально відтворити атмосферу тих днів.',
                            'en' => 'Director Maria Shevchenko together with the playwright conducted over 50 interviews to create the script. The performance features professional actors from Kyiv Academic Drama Theatre. Set design and costumes were created by leading Ukrainian artists who sought to recreate the atmosphere of those days as accurately as possible.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Вистава створена за реальними інтерв\'ю з учасниками Революції Гідності. Режисер - Марія Шевченко. Вистава отримала гран-прі на фестивалі "Золотий Лев".',
                    'en' => 'The performance is based on real interviews with participants of the Revolution of Dignity. Director - Maria Shevchenko. The show won the Grand Prix at the "Golden Lion" festival.',
                ],
                'final_result' => [
                    'uk' => 'Проєкт успішно завершено! Відбулося 10 показів вистави, які відвідали понад 2000 глядачів. Вистава отримала схвальні рецензії у провідних театральних виданнях. 15% коштів від квитків були передані на потреби ЗСУ.',
                    'en' => 'Project successfully completed! 10 performances were held, attended by over 2000 spectators. The show received positive reviews in leading theatre publications. 15% of ticket proceeds were donated to support the Armed Forces.',
                ],
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
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Music->value),
                'currency' => Currency::UAH,
                'budget_goal' => 180000,
                'budget_collected' => 42000,
                'estimated_days' => 150,
                'budget_items' => [
                    ['name' => ['uk' => 'Аранжування та демо', 'en' => 'Arrangements and demos'], 'amount' => 40000],
                    ['name' => ['uk' => 'Оренда студії звукозапису', 'en' => 'Recording studio rental'], 'amount' => 60000],
                    ['name' => ['uk' => 'Запис музикантів', 'en' => 'Musicians recording'], 'amount' => 20000],
                    ['name' => ['uk' => 'Мікс та мастерінг', 'en' => 'Mix and mastering'], 'amount' => 30000],
                    ['name' => ['uk' => 'Дизайн обкладинки', 'en' => 'Cover design'], 'amount' => 10000],
                    ['name' => ['uk' => 'Пресування вінілу та CD', 'en' => 'Vinyl and CD pressing'], 'amount' => 20000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Концепція альбому',
                            'en' => 'Album concept',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => '"Корені та крила" – це музичний проєкт, який поєднує давні українські народні пісні з сучасним електронним звучанням. Альбом містить 12 композицій, кожна з яких є унікальною інтерпретацією автентичної мелодії. Використовуються як традиційні інструменти (бандура, сопілка, цимбали), так і сучасні синтезатори та електронні біти.',
                            'en' => '"Roots and Wings" is a musical project that combines ancient Ukrainian folk songs with modern electronic sound. The album contains 12 compositions, each a unique interpretation of an authentic melody. Both traditional instruments (bandura, sopilka, cymbals) and modern synthesizers and electronic beats are used.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('music'),
                        'image_alt' => [
                            'uk' => 'Обкладинка музичного альбому "Корені та крила"',
                            'en' => 'Cover of the music album "Roots and Wings"',
                        ],
                        'image_caption' => [
                            'uk' => 'Музичний альбом "Корені та крила" поєднує традиції та сучасність',
                            'en' => 'The music album "Roots and Wings" combines tradition and modernity',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Музиканти',
                            'en' => 'Musicians',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'У записі альбому беруть участь відомі українські музиканти: виконавці на традиційних інструментах, вокалісти та електронні продюсери. Аранжування створені таким чином, щоб зберегти автентичність мелодій, але надати їм свіжого, сучасного звучання, яке буде зрозумілим і цікавим молодій аудиторії.',
                            'en' => 'Well-known Ukrainian musicians participate in the album recording: traditional instrument performers, vocalists and electronic producers. The arrangements are created to preserve the authenticity of the melodies while giving them a fresh, modern sound that will be understandable and interesting to young audiences.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('music'),
                        'image_alt' => [
                            'uk' => 'Музиканти, які беруть участь у створенні альбому "Корені та крила"',
                            'en' => 'Musicians participating in the creation of the album "Roots and Wings"',
                        ],
                        'image_caption' => [
                            'uk' => 'У записі альбому беруть участь відомі українські музиканти',
                            'en' => 'Well-known Ukrainian musicians participate in the album recording',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Альбом включає автентичні українські народні пісні у сучасній електронній обробці. Використовуються як традиційні, так і електронні інструменти. Записується у студії "Sound Factory" у Києві.',
                    'en' => 'The album includes authentic Ukrainian folk songs in modern electronic arrangements. Both traditional and electronic instruments are used. Recorded at Sound Factory studio in Kyiv.',
                ],
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
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Visual->value, 'photography'),
                'currency' => Currency::UAH,
                'budget_goal' => 120000,
                'budget_collected' => 0,
                'estimated_days' => 200,
                'budget_items' => [
                    ['name' => ['uk' => 'Пошук та відбір учасників', 'en' => 'Search and selection of participants'], 'amount' => 20000],
                    ['name' => ['uk' => 'Транспорт та проживання', 'en' => 'Transport and accommodation'], 'amount' => 40000],
                    ['name' => ['uk' => 'Фотообладнання', 'en' => 'Photo equipment'], 'amount' => 15000],
                    ['name' => ['uk' => 'Друк фотокниги', 'en' => 'Photo book printing'], 'amount' => 30000],
                    ['name' => ['uk' => 'Організація виставки', 'en' => 'Exhibition organization'], 'amount' => 15000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Про проєкт',
                            'en' => 'About the project',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Фотопроєкт "Обличчя незламних" – це документальна серія портретів українців, які попри всі виклики продовжують творити, працювати і жити. Проєкт охопить представників різних професій з усіх регіонів України: від вчителів та медиків до митців та підприємців.',
                            'en' => 'The photo project "Faces of the Unbreakable" is a documentary series of portraits of Ukrainians who, despite all challenges, continue to create, work and live. The project will cover representatives of various professions from all regions of Ukraine: from teachers and doctors to artists and entrepreneurs.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('photography'),
                        'image_alt' => [
                            'uk' => 'Постер фотопроєкту "Обличчя незламних"',
                            'en' => 'Poster of the photo project "Faces of the Unbreakable"',
                        ],
                        'image_caption' => [
                            'uk' => 'Документальна серія портретів українців, які продовжують творити, працювати і жити',
                            'en' => 'Documentary series of portraits of Ukrainians who continue to create, work and live',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Формат проєкту',
                            'en' => 'Project format',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('photography'),
                        'image_alt' => [
                            'uk' => 'Постер фотопроєкту "Обличчя незламних"',
                            'en' => 'Poster of the photo project "Faces of the Unbreakable"',
                        ],
                        'image_caption' => [
                            'uk' => 'Документальна серія портретів українців, які продовжують творити, працювати і жити',
                            'en' => 'Documentary series of portraits of Ukrainians who continue to create, work and live',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Кожен портрет супроводжується інтерв\'ю з героєм, де вони розповідають свою історію. Фінальним результатом стане фотокнига з 100 портретами, виставка у провідних галереях України та онлайн-галерея. Частина книг буде передана у бібліотеки, школи та культурні центри.',
                            'en' => 'Each portrait is accompanied by an interview with the hero, where they tell their story. The final result will be a photo book with 100 portraits, an exhibition in leading galleries of Ukraine and an online gallery. Part of the books will be donated to libraries, schools and cultural centers.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Проєкт буде містити портрети українців різних професій та регіонів. Кожен портрет супроводжується особистою історією героя. Частина тиражу книги буде передана бібліотекам.',
                    'en' => 'The project will feature portraits of Ukrainians from various professions and regions. Each portrait is accompanied by a personal story of the hero. Part of the book edition will be donated to libraries.',
                ],
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

        // Проєкт 6: Арт-резиденція "Карпати. Діалог з природою" (Оксана) - В процесі
        if ($oksana && ! Project::where('slug', 'art-rezydentsiia-karpaty-dialog-z-pryrodoiu')->exists()) {
            $project = Project::create([
                'user_id' => $oksana->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::InProgress,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'art-rezydentsiia-karpaty-dialog-z-pryrodoiu',
                'title' => [
                    'uk' => 'Арт-резиденція "Карпати. Діалог з природою"',
                    'en' => 'Art Residency "Carpathians. Dialogue with Nature"',
                ],
                'short_description' => [
                    'uk' => 'Міжнародна арт-резиденція для художників у Карпатах з фокусом на екологію та ленд-арт.',
                    'en' => 'International art residency for artists in the Carpathians focused on ecology and land art.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('residency'),
                'tags' => [
                    'uk' => ['арт-резиденція', 'карпати', 'екологія', 'ленд-арт'],
                    'en' => ['art residency', 'carpathians', 'ecology', 'land art'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::FineArt->value, 'digital'),
                'currency' => Currency::UAH,
                'budget_goal' => 220000,
                'budget_collected' => 98000,
                'estimated_days' => 120,
                'budget_items' => [
                    ['name' => ['uk' => 'Відбір та організація (open call)', 'en' => 'Selection and organization (open call)'], 'amount' => 30000],
                    ['name' => ['uk' => 'Проживання учасників', 'en' => 'Participants accommodation'], 'amount' => 80000],
                    ['name' => ['uk' => 'Харчування', 'en' => 'Meals'], 'amount' => 40000],
                    ['name' => ['uk' => 'Матеріали для робіт', 'en' => 'Materials for artworks'], 'amount' => 30000],
                    ['name' => ['uk' => 'Воркшопи та лекції', 'en' => 'Workshops and lectures'], 'amount' => 20000],
                    ['name' => ['uk' => 'Фінальна виставка', 'en' => 'Final exhibition'], 'amount' => 20000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Філософія резиденції',
                            'en' => 'Residency philosophy',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('residency'),
                        'image_alt' => [
                            'uk' => 'Пейзаж Карпат, де проходить арт-резиденція',
                            'en' => 'Carpathian landscape where the art residency takes place',
                        ],
                        'image_caption' => [
                            'uk' => 'Арт-резиденція "Карпати. Діалог з природою" проходить у мальовничому селі Дора в Карпатах',
                            'en' => 'The art residency "Carpathians. Dialogue with Nature" takes place in the picturesque village of Dora in the Carpathians',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Арт-резиденція "Карпати. Діалог з природою" об\'єднує художників з України та світу для спільної роботи в унікальному природному середовищі. Резиденція фокусується на темах екології, сталого розвитку та ленд-арту. Учасники матимуть можливість створювати роботи, використовуючи природні матеріали та ландшафт Карпат.',
                            'en' => 'The art residency "Carpathians. Dialogue with Nature" brings together artists from Ukraine and the world for collaborative work in a unique natural environment. The residency focuses on themes of ecology, sustainable development and land art. Participants will have the opportunity to create works using natural materials and the Carpathian landscape.',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Програма резиденції',
                            'en' => 'Residency program',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Протягом двох місяців художники житимуть та працюватимуть у селі Дора. Програма включає щотижневі воркшопи з запрошеними лекторами, спільні дискусії та індивідуальну роботу над власними проєктами. Кульмінацією резиденції стане відкрита виставка робіт учасників просто неба, де глядачі зможуть побачити створені інсталяції у природному контексті.',
                            'en' => 'For two months, artists will live and work in the village of Dora. The program includes weekly workshops with invited lecturers, group discussions and individual work on personal projects. The culmination of the residency will be an open-air exhibition of participants\' works, where visitors can see the created installations in their natural context.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Резиденція проходить у мальовничому селі Дора в Карпатах. Учасники працюють на перетині мистецтва та екології, створюючи ленд-арт інсталяції. Куратор проєкту - Оксана Петренко. По завершенні буде організована виставка робіт.',
                    'en' => 'The residency takes place in the picturesque village of Dora in the Carpathians. Participants work at the intersection of art and ecology, creating land art installations. Project curator - Oksana Petrenko. An exhibition of works will be organized upon completion.',
                ],
                'announced_at' => now()->subDays(40),
                'planned_completion_at' => now()->addDays(80),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Відбір учасників', 'en' => 'Artist selection'],
                'description' => ['uk' => 'Оп open call та відбір художників', 'en' => 'Open call and artist selection'],
                'budget_planned' => 30000,
                'budget_actual' => 28000,
                'days_planned' => 20,
                'started_at' => now()->subDays(40),
                'completed_at' => now()->subDays(20),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Проведення резиденції', 'en' => 'Residency program'],
                'description' => ['uk' => 'Проживання, робота над проєктами, воркшопи', 'en' => 'Living, project work, workshops'],
                'budget_planned' => 140000,
                'days_planned' => 70,
                'started_at' => now()->subDays(20),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Фінальна виставка', 'en' => 'Final exhibition'],
                'description' => ['uk' => 'Організація виставки робіт учасників резиденції', 'en' => 'Organization of exhibition of participants\' works'],
                'budget_planned' => 50000,
                'days_planned' => 30,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 1000,
                'title' => ['uk' => 'Онлайн-екскурсія', 'en' => 'Online tour'],
                'description' => ['uk' => 'Онлайн-екскурсія резиденцією та знайомство з художниками', 'en' => 'Online tour of the residency and meet the artists'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 5000,
                'title' => ['uk' => 'Каталог виставки', 'en' => 'Exhibition catalog'],
                'description' => ['uk' => 'Друкований каталог фінальної виставки з підписом учасників', 'en' => 'Printed catalog of final exhibition signed by participants'],
                'quantity' => 50,
                'quantity_claimed' => 18,
            ]);
        }

        // Проєкт 7: Короткометражний фільм "Тиша між вибухами" (Тарас) - Оголошений
        if ($taras && ! Project::where('slug', 'korotkometrazhnyi-film-tysha-mizh-vybukhamy')->exists()) {
            $project = Project::create([
                'user_id' => $taras->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Announced,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'korotkometrazhnyi-film-tysha-mizh-vybukhamy',
                'title' => [
                    'uk' => 'Короткометражний фільм "Тиша між вибухами"',
                    'en' => 'Short Film "Silence Between Explosions"',
                ],
                'short_description' => [
                    'uk' => 'Авторський короткометражний фільм про життя цивільних під час війни.',
                    'en' => 'Author short film about civilian life during the war.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('film'),
                'tags' => [
                    'uk' => ['кіно', 'короткометражка', 'драма', 'війна'],
                    'en' => ['film', 'short film', 'drama', 'war'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Visual->value, 'cinema'),
                'currency' => Currency::UAH,
                'budget_goal' => 300000,
                'budget_collected' => 15000,
                'estimated_days' => 140,
                'budget_items' => [
                    ['name' => ['uk' => 'Сценарій та препродакшн', 'en' => 'Script and pre-production'], 'amount' => 40000],
                    ['name' => ['uk' => 'Оренда техніки', 'en' => 'Equipment rental'], 'amount' => 80000],
                    ['name' => ['uk' => 'Зйомки (5 днів)', 'en' => 'Filming (5 days)'], 'amount' => 100000],
                    ['name' => ['uk' => 'Акторам та команді', 'en' => 'Actors and crew'], 'amount' => 50000],
                    ['name' => ['uk' => 'Постпродакшн', 'en' => 'Post-production'], 'amount' => 30000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Синопсис фільму',
                            'en' => 'Film synopsis',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => '"Тиша між вибухами" – це камерна драма про родину, яка намагається зберегти нормальність життя в умовах війни. Фільм розповідає історію одного дня з життя чотирьох людей у невеликій квартирі, де кожен звук за вікном може змінити все. Через мікрокосм однієї родини ми бачимо універсальну історію про силу людського духу, надію та важливість простих речей.',
                            'en' => '"Silence Between Explosions" is an intimate drama about a family trying to maintain normalcy of life during wartime. The film tells the story of one day in the lives of four people in a small apartment, where every sound outside the window can change everything. Through the microcosm of one family, we see a universal story about the strength of the human spirit, hope, and the importance of simple things.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('film'),
                        'image_alt' => [
                            'uk' => 'Постер короткометражного фільму "Тиша між вибухами"',
                            'en' => 'Poster of the short film "Silence Between Explosions"',
                        ],
                        'image_caption' => [
                            'uk' => 'Камерна драма про родину під час війни',
                            'en' => 'An intimate drama about a family during the war',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Творча команда',
                            'en' => 'Creative team',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Режисер та автор сценарію – Тарас Коваленко, відомий своїм чуттєвим підходом до складних тем. У фільмі знімаються провідні українські актори. Оператор – лауреат національних кінопремій. Після завершення фільм буде представлений на міжнародних кінофестивалях.',
                            'en' => 'Director and screenwriter – Taras Kovalenko, known for his sensitive approach to complex themes. Leading Ukrainian actors star in the film. Cinematographer – winner of national film awards. After completion, the film will be presented at international film festivals.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Фільм розповідає історію родини під час війни. Режисер та автор сценарію - Тарас Коваленко. Плануємо участь у міжнародних кінофестивалях.',
                    'en' => 'The film tells the story of a family during the war. Director and screenwriter - Taras Kovalenko. We plan to participate in international film festivals.',
                ],
                'announced_at' => now()->subDays(3),
                'planned_completion_at' => now()->addDays(137),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Препродакшн', 'en' => 'Pre-production'],
                'description' => ['uk' => 'Фіналізація сценарію, кастинг, локейшн скаутинг', 'en' => 'Script finalization, casting, location scouting'],
                'budget_planned' => 40000,
                'days_planned' => 30,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Зйомки', 'en' => 'Production'],
                'description' => ['uk' => 'Основний зйомковий період - 5 днів', 'en' => 'Main filming period - 5 days'],
                'budget_planned' => 180000,
                'days_planned' => 20,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Постпродакшн', 'en' => 'Post-production'],
                'description' => ['uk' => 'Монтаж, колоркорекція, звук', 'en' => 'Editing, color correction, sound'],
                'budget_planned' => 80000,
                'days_planned' => 90,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Подяка у титрах', 'en' => 'Thanks in credits'],
                'description' => ['uk' => 'Ваше ім\'я у фінальних титрах фільму', 'en' => 'Your name in the final credits of the film'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 2000,
                'title' => ['uk' => 'Цифрова копія', 'en' => 'Digital copy'],
                'description' => ['uk' => 'Фільм у високій якості після прем\'єри', 'en' => 'Film in high quality after premiere'],
                'quantity' => 500,
                'quantity_claimed' => 8,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 10000,
                'title' => ['uk' => 'Запрошення на прем\'єру', 'en' => 'Premiere invitation'],
                'description' => ['uk' => 'Запрошення на закриту прем\'єру фільму', 'en' => 'Invitation to private film premiere'],
                'quantity' => 50,
                'quantity_claimed' => 2,
            ]);
        }

        // Проєкт 8: VR-інсталяція "Київ 360" (Марія) - В процесі
        if ($maria && ! Project::where('slug', 'vr-instaliatsiia-kyiv-360')->exists()) {
            $project = Project::create([
                'user_id' => $maria->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::InProgress,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'vr-instaliatsiia-kyiv-360',
                'title' => [
                    'uk' => 'VR-інсталяція "Київ 360"',
                    'en' => 'VR Installation "Kyiv 360"',
                ],
                'short_description' => [
                    'uk' => 'Імерсивна VR-інсталяція з історіями Києва у форматі 360°.',
                    'en' => 'Immersive VR installation with Kyiv stories in 360° format.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('vr'),
                'tags' => [
                    'uk' => ['vr', 'інсталяція', 'київ', 'цифрове мистецтво'],
                    'en' => ['vr', 'installation', 'kyiv', 'digital art'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Visual->value, 'ar'),
                'currency' => Currency::UAH,
                'budget_goal' => 400000,
                'budget_collected' => 185000,
                'estimated_days' => 160,
                'budget_items' => [
                    ['name' => ['uk' => 'Зйомка 360° відео', 'en' => '360° video filming'], 'amount' => 150000],
                    ['name' => ['uk' => 'Постпродакшн та стітчинг', 'en' => 'Post-production and stitching'], 'amount' => 100000],
                    ['name' => ['uk' => 'VR-обладнання', 'en' => 'VR equipment'], 'amount' => 80000],
                    ['name' => ['uk' => 'Інтерактивна розробка', 'en' => 'Interactive development'], 'amount' => 50000],
                    ['name' => ['uk' => 'Монтаж інсталяції', 'en' => 'Installation setup'], 'amount' => 20000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Концепція VR-досвіду',
                            'en' => 'VR experience concept',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'VR-інсталяція "Київ 360" – це імерсивна подорож столицею України очима різних людей. За 15 хвилин глядачі відвідають 8 знакових локацій Києва і почують історії від мешканців міста: від студента-архітектора до волонтера, від художниці до підприємця. Кожна локація розкриває унікальну перспективу на життя міста.',
                            'en' => 'The VR installation "Kyiv 360" is an immersive journey through the capital of Ukraine through the eyes of different people. In 15 minutes, viewers will visit 8 iconic locations in Kyiv and hear stories from city residents: from a student architect to a volunteer, from an artist to an entrepreneur. Each location reveals a unique perspective on city life.',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Технологія створення',
                            'en' => 'Creation technology',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Для створення проєкту використовується найсучасніше обладнання для зйомки 360° відео у роздільній здатності 8K. Після зйомки відбувається складний процес стітчингу та колоркорекції. Інсталяція включає інтерактивні елементи – глядачі можуть вибирати, які історії слухати, та досліджувати простір у власному темпі.',
                            'en' => 'The most modern equipment for shooting 360° video in 8K resolution is used to create the project. After filming, there is a complex process of stitching and color correction. The installation includes interactive elements – viewers can choose which stories to listen to and explore the space at their own pace.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Унікальна VR-інсталяція дозволить відвідувачам побачити Київ очима різних людей. Включає історії мешканців, митців, захисників міста. Використовується найсучасніше обладнання для зйомки 360°.',
                    'en' => 'A unique VR installation will allow visitors to see Kyiv through the eyes of different people. Includes stories of residents, artists, city defenders. The most modern equipment is used for 360° filming.',
                ],
                'announced_at' => now()->subDays(60),
                'planned_completion_at' => now()->addDays(100),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Зйомка відео', 'en' => 'Video filming'],
                'description' => ['uk' => 'Зйомка 360° відео у 8 локаціях Києва', 'en' => 'Filming 360° video in 8 Kyiv locations'],
                'budget_planned' => 150000,
                'budget_actual' => 145000,
                'days_planned' => 45,
                'started_at' => now()->subDays(60),
                'completed_at' => now()->subDays(15),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Постпродакшн', 'en' => 'Post-production'],
                'description' => ['uk' => 'Обробка відео, стітчинг, колоркорекція', 'en' => 'Video processing, stitching, color correction'],
                'budget_planned' => 150000,
                'days_planned' => 60,
                'started_at' => now()->subDays(15),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Створення інсталяції', 'en' => 'Installation creation'],
                'description' => ['uk' => 'Монтаж інсталяції, налаштування обладнання', 'en' => 'Installation setup, equipment configuration'],
                'budget_planned' => 100000,
                'days_planned' => 55,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 1000,
                'title' => ['uk' => 'Ранній доступ', 'en' => 'Early access'],
                'description' => ['uk' => 'Запрошення на закритий показ для меценатів', 'en' => 'Invitation to private screening for patrons'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 3000,
                'title' => ['uk' => 'VR-досвід вдома', 'en' => 'VR experience at home'],
                'description' => ['uk' => 'Доступ до VR-досвіду на власних окулярах', 'en' => 'Access to VR experience on your own headset'],
                'quantity' => 200,
                'quantity_claimed' => 67,
            ]);
        }

        // Проєкт 9: Вуличний мурал "Крила свободи" (Дмитро) - Завершений
        if ($dmytro && ! Project::where('slug', 'vulychnyi-mural-kryla-svobody')->exists()) {
            $project = Project::create([
                'user_id' => $dmytro->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Completed,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'vulychnyi-mural-kryla-svobody',
                'title' => [
                    'uk' => 'Вуличний мурал "Крила свободи"',
                    'en' => 'Street Mural "Wings of Freedom"',
                ],
                'short_description' => [
                    'uk' => 'Великий мурал на фасаді будинку, присвячений свободі та стійкості.',
                    'en' => 'Large mural on a building facade dedicated to freedom and resilience.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('mural'),
                'tags' => [
                    'uk' => ['мурал', 'стріт-арт', 'місто', 'свобода'],
                    'en' => ['mural', 'street art', 'city', 'freedom'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Visual->value, 'photography'),
                'currency' => Currency::UAH,
                'budget_goal' => 90000,
                'budget_collected' => 90000,
                'estimated_days' => 30,
                'budget_items' => [
                    ['name' => ['uk' => 'Ескізи та дізайн', 'en' => 'Sketches and design'], 'amount' => 15000],
                    ['name' => ['uk' => 'Фарби та матеріали', 'en' => 'Paints and materials'], 'amount' => 30000],
                    ['name' => ['uk' => 'Оренда підйомника', 'en' => 'Lift rental'], 'amount' => 25000],
                    ['name' => ['uk' => 'Робота асистентів', 'en' => 'Assistants work'], 'amount' => 15000],
                    ['name' => ['uk' => 'Захисне покриття', 'en' => 'Protective coating'], 'amount' => 5000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Символізм муралу',
                            'en' => 'Mural symbolism',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Мурал "Крила свободи" розташований на фасаді будинку на вулиці Хрещатик і став новою візитівкою столиці. Центральний образ – абстрактні крила, що символізують прагнення до свободи та незалежності. Композиція поєднує елементи реалізму та графіки, створюючи динамічний візуальний ефект.',
                            'en' => 'The mural "Wings of Freedom" is located on the facade of a building on Khreshchatyk Street and has become a new landmark of the capital. The central image is abstract wings symbolizing the desire for freedom and independence. The composition combines elements of realism and graphics, creating a dynamic visual effect.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('mural'),
                        'image_alt' => [
                            'uk' => 'Вуличний мурал "Крила свободи" на вулиці Хрещатик',
                            'en' => 'Street mural "Wings of Freedom" on Khreshchatyk Street',
                        ],
                        'image_caption' => [
                            'uk' => 'Динамічна композиція з абстрактними крилами на фасаді будинку',
                            'en' => 'Dynamic composition with abstract wings on the building facade',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Процес створення',
                            'en' => 'Creation process',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Робота над муралом тривала 10 днів. Команда з чотирьох художників працювала з автопідйомника, використовуючи спеціальні фасадні фарби, стійкі до погодних умов. Спочатку на стіну було нанесено базовий шар, потім основні кольори та контури, і фінально – деталі та захисне покриття. Мурал став вірусним у соцмережах і залучив понад 10,000 відвідувачів за перший тиждень.',
                            'en' => 'Work on the mural lasted 10 days. A team of four artists worked from a lift, using special facade paints resistant to weather conditions. First, a base layer was applied to the wall, then the main colors and contours, and finally – details and protective coating. The mural went viral on social media and attracted over 10,000 visitors in the first week.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Мурал розміщений на центральній вулиці Києва. Зображує символічну композицію з крилами як символом свободи. Робота виконана за 10 днів бригадою з 4 художників.',
                    'en' => 'The mural is located on the main street of Kyiv. It depicts a symbolic composition with wings as a symbol of freedom. The work was completed in 10 days by a team of 4 artists.',
                ],
                'final_result' => [
                    'uk' => 'Мурал успішно завершено! Роботу було виконано за 10 днів (на 5 днів швидше плану). Мурал став новою туристичною локацією міста. Отримано понад 50 публікацій у ЗМІ. Роботу відвідали більше 10,000 людей за перший тиждень.',
                    'en' => 'Mural successfully completed! The work was done in 10 days (5 days ahead of schedule). The mural has become a new tourist location in the city. Received over 50 media publications. More than 10,000 people visited the work in the first week.',
                ],
                'announced_at' => now()->subDays(60),
                'planned_completion_at' => now()->subDays(30),
                'completed_at' => now()->subDays(25),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Дизайн та підготовка', 'en' => 'Design and preparation'],
                'description' => ['uk' => 'Створення ескізів, узгодження з міською владою', 'en' => 'Creating sketches, coordination with city authorities'],
                'budget_planned' => 20000,
                'budget_actual' => 18000,
                'days_planned' => 10,
                'started_at' => now()->subDays(45),
                'completed_at' => now()->subDays(35),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Виконання муралу', 'en' => 'Mural execution'],
                'description' => ['uk' => 'Малювання муралу на фасаді', 'en' => 'Painting the mural on the facade'],
                'budget_planned' => 60000,
                'budget_actual' => 62000,
                'days_planned' => 15,
                'started_at' => now()->subDays(35),
                'completed_at' => now()->subDays(25),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Фінальне покриття', 'en' => 'Final coating'],
                'description' => ['uk' => 'Нанесення захисного покриття', 'en' => 'Applying protective coating'],
                'budget_planned' => 10000,
                'budget_actual' => 10000,
                'days_planned' => 5,
                'started_at' => now()->subDays(28),
                'completed_at' => now()->subDays(25),
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Фото з муралом', 'en' => 'Photo with mural'],
                'description' => ['uk' => 'Підписане фото муралу формату А4', 'en' => 'Signed photo of mural in A4 format'],
                'quantity' => 100,
                'quantity_claimed' => 100,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 3000,
                'title' => ['uk' => 'Авторський ескіз', 'en' => 'Author\'s sketch'],
                'description' => ['uk' => 'Оригінальний підписаний ескіз муралу', 'en' => 'Original signed sketch of the mural'],
                'quantity' => 10,
                'quantity_claimed' => 10,
            ]);
        }

        // Проєкт 10: Дитяча ілюстрована книга "Казки незламних" (Анна) - Оголошений
        if ($anna && ! Project::where('slug', 'dytiacha-knyha-kazky-nezlamnykh')->exists()) {
            $project = Project::create([
                'user_id' => $anna->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Announced,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'dytiacha-knyha-kazky-nezlamnykh',
                'title' => [
                    'uk' => 'Дитяча ілюстрована книга "Казки незламних"',
                    'en' => 'Children\'s Illustrated Book "Tales of the Unbreakable"',
                ],
                'short_description' => [
                    'uk' => 'Збірка дитячих казок про сміливість, дружбу та надію з авторськими ілюстраціями.',
                    'en' => 'Collection of children\'s tales about courage, friendship and hope with original illustrations.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('illustration'),
                'tags' => [
                    'uk' => ['дитяча книга', 'ілюстрація', 'казки', 'діти'],
                    'en' => ['children book', 'illustration', 'tales', 'kids'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Literature->value, 'prose'),
                'currency' => Currency::UAH,
                'budget_goal' => 140000,
                'budget_collected' => 12000,
                'estimated_days' => 110,
                'budget_items' => [
                    ['name' => ['uk' => 'Написання казок', 'en' => 'Writing tales'], 'amount' => 20000],
                    ['name' => ['uk' => 'Створення ілюстрацій', 'en' => 'Creating illustrations'], 'amount' => 40000],
                    ['name' => ['uk' => 'Редагування та коректура', 'en' => 'Editing and proofreading'], 'amount' => 15000],
                    ['name' => ['uk' => 'Дизайн та верстка', 'en' => 'Design and layout'], 'amount' => 15000],
                    ['name' => ['uk' => 'Друк (1000 прим.)', 'en' => 'Printing (1000 copies)'], 'amount' => 50000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Про книгу',
                            'en' => 'About the book',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => '"Казки незламних" – це збірка з 10 авторських казок для дітей віком від 4 до 10 років. Кожна казка розповідає про важливі людські якості: сміливість, доброту, чесність, дружбу та надію. Головні герої – українські діти та фантастичні персонажі – долають різні перешкоди, навчаючись вірити в себе та підтримувати один одного.',
                            'en' => '"Tales of the Unbreakable" is a collection of 10 original fairy tales for children aged 4 to 10 years. Each tale tells about important human qualities: courage, kindness, honesty, friendship and hope. The main characters – Ukrainian children and fantastic characters – overcome various obstacles, learning to believe in themselves and support each other.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('illustration'),
                        'image_alt' => [
                            'uk' => 'Обкладинка дитячої книги "Казки незламних" з ілюстраціями',
                            'en' => 'Cover of the children\'s book "Tales of the Unbreakable" with illustrations',
                        ],
                        'image_caption' => [
                            'uk' => 'Збірка 10 авторських казок про мужність та доброту для дітей',
                            'en' => 'Collection of 10 original fairy tales about courage and kindness for children',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Ілюстрації та оформлення',
                            'en' => 'Illustrations and design',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Книга містить 40 повнокольорових ілюстрацій, виконаних у теплій, казковій манері. Кожна казка супроводжується 4 великими ілюстраціями, які допомагають дітям краще уявити героїв та події. Книга друкується на якісному папері з твердою обкладинкою. Частина тиражу буде безкоштовно передана дитячим будинкам, лікарням та бібліотекам.',
                            'en' => 'The book contains 40 full-color illustrations done in a warm, fairy-tale manner. Each tale is accompanied by 4 large illustrations that help children better imagine the characters and events. The book is printed on quality paper with a hardcover. Part of the edition will be donated free of charge to orphanages, hospitals and libraries.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Книга містить 10 авторських казок про мужність, доброту та надію. Кожна казка супроводжується 4 повнокольоровими ілюстраціями. Частина тиражу буде подарована дитячим будинкам та лікарням.',
                    'en' => 'The book contains 10 original fairy tales about courage, kindness and hope. Each tale is accompanied by 4 full-color illustrations. Part of the edition will be donated to orphanages and hospitals.',
                ],
                'announced_at' => now()->subDays(5),
                'planned_completion_at' => now()->addDays(105),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Написання текстів', 'en' => 'Writing texts'],
                'description' => ['uk' => 'Створення 10 казок, редагування', 'en' => 'Creating 10 tales, editing'],
                'budget_planned' => 35000,
                'days_planned' => 40,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Ілюстрації', 'en' => 'Illustrations'],
                'description' => ['uk' => 'Створення 40 ілюстрацій', 'en' => 'Creating 40 illustrations'],
                'budget_planned' => 40000,
                'days_planned' => 45,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Верстка та друк', 'en' => 'Layout and printing'],
                'description' => ['uk' => 'Дизайн макету, верстка, друк тиражу', 'en' => 'Layout design, typesetting, printing'],
                'budget_planned' => 65000,
                'days_planned' => 25,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 300,
                'title' => ['uk' => 'Електронна книга', 'en' => 'E-book'],
                'description' => ['uk' => 'PDF-версія книги з усіма ілюстраціями', 'en' => 'PDF version of the book with all illustrations'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 800,
                'title' => ['uk' => 'Друкована книга', 'en' => 'Printed book'],
                'description' => ['uk' => 'Підписаний примірник книги', 'en' => 'Signed copy of the book'],
                'quantity' => 500,
                'quantity_claimed' => 18,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 3000,
                'title' => ['uk' => 'Книга + оригінальна ілюстрація', 'en' => 'Book + original illustration'],
                'description' => ['uk' => 'Книга + одна оригінальна ілюстрація на папері', 'en' => 'Book + one original illustration on paper'],
                'quantity' => 40,
                'quantity_claimed' => 3,
            ]);
        }

        // =====================================================
        // Проєкти 11–15 (додаткові демо-проєкти)
        // =====================================================

        // Проєкт 11: Балетна постановка (Марія) - Announced
        if ($maria && ! Project::where('slug', 'baletna-postanovka-tilo-i-povitrya')->exists()) {
            $project = Project::create([
                'user_id' => $maria->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Announced,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'baletna-postanovka-tilo-i-povitrya',
                'title' => [
                    'uk' => 'Балетна постановка "Тіло і повітря"',
                    'en' => 'Ballet Production "Body and Air"',
                ],
                'short_description' => [
                    'uk' => 'Сучасний балет про крихкість та силу людського тіла.',
                    'en' => 'Contemporary ballet about fragility and strength of the human body.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('ballet'),
                'tags' => [
                    'uk' => ['балет', 'танець', 'сучасний балет'],
                    'en' => ['ballet', 'dance', 'contemporary ballet'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Scenic->value, 'choreography'),
                'currency' => Currency::UAH,
                'budget_goal' => 260000,
                'budget_collected' => 18000,
                'estimated_days' => 140,
                'budget_items' => [
                    ['name' => ['uk' => 'Хореографія та репетиції', 'en' => 'Choreography and rehearsals'], 'amount' => 80000],
                    ['name' => ['uk' => 'Костюми', 'en' => 'Costumes'], 'amount' => 50000],
                    ['name' => ['uk' => 'Сценографія та світло', 'en' => 'Set design and lighting'], 'amount' => 60000],
                    ['name' => ['uk' => 'Оренда залу', 'en' => 'Hall rental'], 'amount' => 50000],
                    ['name' => ['uk' => 'Музичний супровід', 'en' => 'Musical accompaniment'], 'amount' => 20000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Концепція балету',
                            'en' => 'Ballet concept',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Сучасна балетна постановка "Тіло і повітря" досліджує тему крихкості та водночас незламності людського тіла. Вистава поєднує елементи класичного балету з контемпорарі-танцем, створюючи унікальну хореографічну мову. Восьмеро танцюристів створюють на сцені живі скульптури, що розповідають історію без слів – через рух, простір та музику.',
                            'en' => 'The contemporary ballet production "Body and Air" explores the theme of fragility and at the same time resilience of the human body. The performance combines elements of classical ballet with contemporary dance, creating a unique choreographic language. Eight dancers create living sculptures on stage that tell a story without words – through movement, space and music.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('ballet'),
                        'image_alt' => [
                            'uk' => 'Панер сучасної балетної постановки "Тіло і повітря"',
                            'en' => 'Poster of the contemporary ballet "Body and Air"',
                        ],
                        'image_caption' => [
                            'uk' => 'Сучасна хореографія, що досліджує крихкість та силу людського тіла',
                            'en' => 'Contemporary choreography exploring fragility and strength of the human body',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Творча команда',
                            'en' => 'Creative team',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Хореографиня Марія Шевченко працює з талановитими солістами провідних українських балетних труп. Музика створена спеціально для цієї постановки українським композитором-експериментатором. Сценографія та костюми підкреслюють мінімалістичну естетику вистави, дозволяючи глядачам зосередитися на красі та виразності руху.',
                            'en' => 'Choreographer Maria Shevchenko works with talented soloists from leading Ukrainian ballet companies. The music was created especially for this production by a Ukrainian experimental composer. Set design and costumes emphasize the minimalist aesthetics of the performance, allowing viewers to focus on the beauty and expressiveness of movement.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Балет створений відомою хореографинею Марією Шевченко. Вистава поєднує елементи класичного та сучасного балету. Музика написана спеціально для цієї постановки українським композитором.',
                    'en' => 'The ballet was created by renowned choreographer Maria Shevchenko. The production combines elements of classical and contemporary ballet. The music was specially written for this production by a Ukrainian composer.',
                ],
                'announced_at' => now()->subDays(4),
                'planned_completion_at' => now()->addDays(136),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Створення хореографії', 'en' => 'Choreography creation'],
                'description' => ['uk' => 'Розробка хореографії, підбір музики', 'en' => 'Choreography development, music selection'],
                'budget_planned' => 40000,
                'days_planned' => 30,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Репетиції', 'en' => 'Rehearsals'],
                'description' => ['uk' => 'Репетиційний період з артистами балету', 'en' => 'Rehearsal period with ballet artists'],
                'budget_planned' => 90000,
                'days_planned' => 80,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Прем\'єра та покази', 'en' => 'Premiere and performances'],
                'description' => ['uk' => 'Прем\'єра та 6 показів вистави', 'en' => 'Premiere and 6 performances'],
                'budget_planned' => 130000,
                'days_planned' => 30,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Програмка вистави', 'en' => 'Performance program'],
                'description' => ['uk' => 'Підписана програмка з подякою', 'en' => 'Signed program with thanks'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 1500,
                'title' => ['uk' => 'Квиток на прем\'єру', 'en' => 'Premiere ticket'],
                'description' => ['uk' => 'Квиток на прем\'єрний показ', 'en' => 'Ticket to premiere show'],
                'quantity' => 150,
                'quantity_claimed' => 12,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 5000,
                'title' => ['uk' => 'Зустріч з артистами', 'en' => 'Meet the artists'],
                'description' => ['uk' => '2 VIP-квитки + зустріч з артистами після вистави', 'en' => '2 VIP tickets + meet & greet with artists after the show'],
                'quantity' => 20,
                'quantity_claimed' => 2,
            ]);
        }

        // Проєкт 12: Документальний подкаст (Дмитро) - InProgress
        if ($dmytro && ! Project::where('slug', 'dokumentalnyi-podkast-holosy-ukrainy')->exists()) {
            $project = Project::create([
                'user_id' => $dmytro->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::InProgress,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'dokumentalnyi-podkast-holosy-ukrainy',
                'title' => [
                    'uk' => 'Документальний подкаст "Голоси України"',
                    'en' => 'Documentary Podcast "Voices of Ukraine"',
                ],
                'short_description' => [
                    'uk' => 'Серія подкастів з реальними історіями людей з різних регіонів.',
                    'en' => 'Podcast series with real stories from people across regions.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('podcast'),
                'tags' => [
                    'uk' => ['подкаст', 'документалістика', 'історії'],
                    'en' => ['podcast', 'documentary', 'stories'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Music->value),
                'currency' => Currency::UAH,
                'budget_goal' => 110000,
                'budget_collected' => 54000,
                'estimated_days' => 90,
                'budget_items' => [
                    ['name' => ['uk' => 'Пошук героїв та підготовка', 'en' => 'Finding heroes and preparation'], 'amount' => 20000],
                    ['name' => ['uk' => 'Поїздки та інтерв\'ю', 'en' => 'Trips and interviews'], 'amount' => 30000],
                    ['name' => ['uk' => 'Обладнання для запису', 'en' => 'Recording equipment'], 'amount' => 25000],
                    ['name' => ['uk' => 'Монтаж та постпродакшн', 'en' => 'Editing and post-production'], 'amount' => 25000],
                    ['name' => ['uk' => 'Просування', 'en' => 'Promotion'], 'amount' => 10000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Концепція подкасту',
                            'en' => 'Podcast concept',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => '"Голоси України" – це документальний подкаст, який розповідає реальні історії людей з різних куточків країни. Кожен епізод – це глибоке інтерв\'ю тривалістю 30-40 хвилин, де герої діляться своїм життєвим досвідом, мріями, викликами та надіями. Проєкт має на меті показати різноманіття українського досвіду через особисті історії звичайних людей.',
                            'en' => '"Voices of Ukraine" is a documentary podcast that tells real stories of people from different parts of the country. Each episode is an in-depth interview lasting 30-40 minutes, where heroes share their life experiences, dreams, challenges and hopes. The project aims to show the diversity of Ukrainian experience through personal stories of ordinary people.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('podcast'),
                        'image_alt' => [
                            'uk' => 'Постер документального подкасту "Голоси України"',
                            'en' => 'Poster of the documentary podcast "Voices of Ukraine"',
                        ],
                        'image_caption' => [
                            'uk' => 'Серія глибоких інтерв\'ю з реальними історіями українців',
                            'en' => 'Series of in-depth interviews with real stories of Ukrainians',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Формат та дистрибуція',
                            'en' => 'Format and distribution',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Перший сезон складається з 12 епізодів, які виходитимуть щотижня. Подкаст буде доступний на всіх основних платформах: Spotify, Apple Podcasts, Google Podcasts та YouTube. Кожен епізод супроводжується детальним description і таймкодами для зручності слухачів. Автор проєкту також створює bonus-контент для меценатів: розширені версії інтерв\'ю та закулісні матеріали.',
                            'en' => 'The first season consists of 12 episodes that will be released weekly. The podcast will be available on all major platforms: Spotify, Apple Podcasts, Google Podcasts and YouTube. Each episode is accompanied by a detailed description and timestamps for listeners\' convenience. The project author also creates bonus content for patrons: extended versions of interviews and behind-the-scenes materials.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Подкаст розповідає історії звичайних українців з різних куточків країни. Кожен епізод - це глибоке інтерв\'ю про життя, мрії, виклики. Вийде по 1 епізоду на тиждень.',
                    'en' => 'The podcast tells stories of ordinary Ukrainians from different parts of the country. Each episode is an in-depth interview about life, dreams, challenges. One episode per week will be released.',
                ],
                'announced_at' => now()->subDays(25),
                'planned_completion_at' => now()->addDays(65),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Підготовка', 'en' => 'Preparation'],
                'description' => ['uk' => 'Пошук героїв, планування сезону', 'en' => 'Finding heroes, season planning'],
                'budget_planned' => 20000,
                'budget_actual' => 18000,
                'days_planned' => 15,
                'started_at' => now()->subDays(25),
                'completed_at' => now()->subDays(10),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Запис епізодів', 'en' => 'Recording episodes'],
                'description' => ['uk' => 'Інтерв\'ю та запис 12 епізодів', 'en' => 'Interviews and recording of 12 episodes'],
                'budget_planned' => 60000,
                'days_planned' => 50,
                'started_at' => now()->subDays(10),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Публікація', 'en' => 'Publishing'],
                'description' => ['uk' => 'Монтаж та публікація на платформах', 'en' => 'Editing and publishing on platforms'],
                'budget_planned' => 30000,
                'days_planned' => 25,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 200,
                'title' => ['uk' => 'Ранній доступ', 'en' => 'Early access'],
                'description' => ['uk' => 'Доступ до епізодів на 3 дні раніше релізу', 'en' => 'Access to episodes 3 days before release'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 1000,
                'title' => ['uk' => 'Bonus контент', 'en' => 'Bonus content'],
                'description' => ['uk' => 'Додаткові неопубліковані матеріали з інтерв\'ю', 'en' => 'Additional unpublished materials from interviews'],
                'quantity' => 200,
                'quantity_claimed' => 58,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 5000,
                'title' => ['uk' => 'Особиста подяка', 'en' => 'Personal thanks'],
                'description' => ['uk' => 'Особиста подяка від ведучого в епізоді', 'en' => 'Personal thanks from the host in an episode'],
                'quantity' => 10,
                'quantity_claimed' => 3,
            ]);
        }

        // Проєкт 13: Артбук ілюстрацій (Оксана) - Announced
        if ($oksana && ! Project::where('slug', 'artbuk-sny-ukrainy')->exists()) {
            $project = Project::create([
                'user_id' => $oksana->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Announced,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'artbuk-sny-ukrainy',
                'title' => [
                    'uk' => 'Артбук ілюстрацій "Сни України"',
                    'en' => 'Illustration Artbook "Dreams of Ukraine"',
                ],
                'short_description' => [
                    'uk' => 'Колекційний артбук з ілюстраціями сучасних українських художників.',
                    'en' => 'Collectible artbook with illustrations by contemporary Ukrainian artists.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('artbook'),
                'tags' => [
                    'uk' => ['артбук', 'ілюстрація', 'книга'],
                    'en' => ['artbook', 'illustration', 'book'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Visual->value, 'video'),
                'currency' => Currency::UAH,
                'budget_goal' => 190000,
                'budget_collected' => 22000,
                'estimated_days' => 100,
                'budget_items' => [
                    ['name' => ['uk' => 'Гонорари художникам', 'en' => 'Artist fees'], 'amount' => 75000],
                    ['name' => ['uk' => 'Редакційна робота', 'en' => 'Editorial work'], 'amount' => 20000],
                    ['name' => ['uk' => 'Дизайн та верстка', 'en' => 'Design and layout'], 'amount' => 25000],
                    ['name' => ['uk' => 'Друк (500 прим.)', 'en' => 'Printing (500 copies)'], 'amount' => 60000],
                    ['name' => ['uk' => 'Упаковка та доставка', 'en' => 'Packaging and delivery'], 'amount' => 10000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Про артбук',
                            'en' => 'About the artbook',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Артбук "Сни України" об\'єднує роботи 15 найкращих українських ілюстраторів сучасності. Кожен художник представляє 8 авторських ілюстрацій на тему "Сни України" – особисте бачення минулого, сучасного та майбутнього нашої країни. Книга має формат альбому А4 з твердою обкладинкою та друкується на преміальному папері з UF-лаком.',
                            'en' => 'The artbook "Dreams of Ukraine" brings together works of 15 best contemporary Ukrainian illustrators. Each artist presents 8 original illustrations on the theme "Dreams of Ukraine" – a personal vision of the past, present and future of our country. The book has an A4 album format with a hardcover and is printed on premium paper with UV coating.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('artbook'),
                        'image_alt' => [
                            'uk' => 'Обкладинка артбуку "Сни України" з ілюстраціями 15 художників',
                            'en' => 'Cover of the artbook "Dreams of Ukraine" with illustrations by 15 artists',
                        ],
                        'image_caption' => [
                            'uk' => 'Колекційне видання українських ілюстрацій на А4 папері з твердою обкладинкою',
                            'en' => 'Collectible edition of Ukrainian illustrations on A4 premium paper with hardcover',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Художники та стилі',
                            'en' => 'Artists and styles',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'У артбуці представлені різноманітні стилі ілюстрації: від класичної акварелі до цифрового арту, від детальної графіки до мінімалістичних композицій. Кожен художник супроводжує свої роботи коротким есе про свій творчий процес та джерела натхнення. Артбук стане не лише колекційним виданням, а й документом часу, що фіксує погляд сучасних українських митців на свою країну.',
                            'en' => 'The artbook features various illustration styles: from classical watercolor to digital art, from detailed graphics to minimalist compositions. Each artist accompanies their work with a short essay about their creative process and sources of inspiration. The artbook will become not only a collectible edition, but also a document of time that captures the view of contemporary Ukrainian artists on their country.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Артбук об\'єднує роботи 15 найкращих українських ілюстраторів. Кожен художник представляє 8 робіт на тему "Сни України". Книга буде друкуватися на преміальному папері з UF-лаком.',
                    'en' => 'The artbook brings together works of 15 best Ukrainian illustrators. Each artist presents 8 works on the theme "Dreams of Ukraine". The book will be printed on premium paper with UV coating.',
                ],
                'announced_at' => now()->subDays(6),
                'planned_completion_at' => now()->addDays(94),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Збір робіт', 'en' => 'Collecting works'],
                'description' => ['uk' => 'Відбір художників, створення ілюстрацій', 'en' => 'Artist selection, creating illustrations'],
                'budget_planned' => 95000,
                'days_planned' => 50,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Верстка', 'en' => 'Layout'],
                'description' => ['uk' => 'Дизайн та верстка артбуку', 'en' => 'Artbook design and layout'],
                'budget_planned' => 25000,
                'days_planned' => 25,
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Друк та розсилка', 'en' => 'Printing and shipping'],
                'description' => ['uk' => 'Друк тиражу та відправка меценатам', 'en' => 'Print run and shipping to patrons'],
                'budget_planned' => 70000,
                'days_planned' => 25,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Цифрова версія', 'en' => 'Digital version'],
                'description' => ['uk' => 'PDF артбуку у високій якості', 'en' => 'High quality PDF of artbook'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 1500,
                'title' => ['uk' => 'Друкований артбук', 'en' => 'Printed artbook'],
                'description' => ['uk' => 'Підписаний примірник артбуку', 'en' => 'Signed copy of artbook'],
                'quantity' => 400,
                'quantity_claimed' => 18,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 5000,
                'title' => ['uk' => 'Колекційне видання', 'en' => 'Collector\'s edition'],
                'description' => ['uk' => 'Артбук + принт однієї з ілюстрацій (А3)', 'en' => 'Artbook + print of one illustration (A3)'],
                'quantity' => 50,
                'quantity_claimed' => 4,
            ]);
        }

        // Проєкт 14: Світлова інсталяція (Тарас) - Completed
        if ($taras && ! Project::where('slug', 'svitlova-instaliatsiia-serce-mista')->exists()) {
            $project = Project::create([
                'user_id' => $taras->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::Completed,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'svitlova-instaliatsiia-serce-mista',
                'title' => [
                    'uk' => 'Світлова інсталяція "Серце міста"',
                    'en' => 'Light Installation "Heart of the City"',
                ],
                'short_description' => [
                    'uk' => 'Інтерактивна світлова інсталяція у центрі міста.',
                    'en' => 'Interactive light installation in the city center.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('light-art'),
                'tags' => [
                    'uk' => ['світлова інсталяція', 'медіа-арт', 'місто'],
                    'en' => ['light installation', 'media art', 'city'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Visual->value, 'video'),
                'currency' => Currency::UAH,
                'budget_goal' => 130000,
                'budget_collected' => 130000,
                'estimated_days' => 45,
                'budget_items' => [
                    ['name' => ['uk' => 'LED-обладнання', 'en' => 'LED equipment'], 'amount' => 60000],
                    ['name' => ['uk' => 'Програмування та інтерактив', 'en' => 'Programming and interactivity'], 'amount' => 30000],
                    ['name' => ['uk' => 'Монтаж конструкції', 'en' => 'Structure installation'], 'amount' => 25000],
                    ['name' => ['uk' => 'Дозволи та документація', 'en' => 'Permits and documentation'], 'amount' => 10000],
                    ['name' => ['uk' => 'Технічна підтримка (1 міс.)', 'en' => 'Technical support (1 month)'], 'amount' => 5000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Концепція інсталяції',
                            'en' => 'Installation concept',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Світлова інсталяція "Серце міста" – це інтерактивний медіа-арт об\'єкт розміром 10x6 метрів, розташований на Площі Незалежності у центрі Києва. Інсталяція використовує сучасні LED-технології та датчики руху, створюючи живу, що реагує на перехожих, світлову картину. Кожен рух людини біля інсталяції породжує унікальний візуальний патерн – так мистецтво стає діалогом між твором і глядачем.',
                            'en' => 'The light installation "Heart of the City" is an interactive media art object measuring 10x6 meters, located on Independence Square in the center of Kyiv. The installation uses modern LED technology and motion sensors, creating a living light picture that responds to passers-by. Each movement of a person near the installation generates a unique visual pattern – thus art becomes a dialogue between the work and the viewer.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('light-art'),
                        'image_alt' => [
                            'uk' => 'Інтерактивна світлова інсталяція "Серце міста" на Площі Незалежності',
                            'en' => 'Interactive light installation "Heart of the City" on Independence Square',
                        ],
                        'image_caption' => [
                            'uk' => 'Медіа-арт об\'єкт розміром 10x6 метрів з LED-технологією та датчиками руху',
                            'en' => 'Media art object 10x6 meters with LED technology and motion sensors',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Реалізація та результати',
                            'en' => 'Implementation and results',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Інсталяція була створена за 45 днів та працює щовечора з 18:00 до 23:00. За перший місяць роботи її побачили понад 100,000 людей, а відео з інсталяцією набрали понад 500,000 переглядів у соціальних мережах. Міська рада, вражена популярністю проєкту, вирішила продовжити роботу інсталяції на додаткові 6 місяців. "Серце міста" стало новою визначною точкою Києва та улюбленим місцем для фотосесій.',
                            'en' => 'The installation was created in 45 days and operates every evening from 18:00 to 23:00. In the first month of operation, over 100,000 people saw it, and videos of the installation gained over 500,000 views on social media. The city council, impressed by the project\'s popularity, decided to extend the installation\'s operation for an additional 6 months. "Heart of the City" has become a new landmark of Kyiv and a favorite place for photo shoots.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Інсталяція "Серце міста" реагує на рух перехожих, створюючи унікальні світлові візерунки. Працює щовечора з 18:00 до 23:00. Автор - медіа-художник Тарас Коваленко.',
                    'en' => 'The installation "Heart of the City" responds to the movement of passers-by, creating unique light patterns. Works every evening from 18:00 to 23:00. Author - media artist Taras Kovalenko.',
                ],
                'final_result' => [
                    'uk' => 'Інсталяція встановлена та працює! За перший місяць її побачили понад 100,000 людей. Інсталяція стала вірусною в соціальних мережах з понад 500,000 переглядів відео. Міська рада вирішила продовжити роботу інсталяції на 6 місяців.',
                    'en' => 'Installation installed and working! Over 100,000 people saw it in the first month. The installation went viral on social media with over 500,000 video views. The city council decided to extend the installation\'s operation for 6 months.',
                ],
                'announced_at' => now()->subDays(80),
                'planned_completion_at' => now()->subDays(35),
                'completed_at' => now()->subDays(30),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Дизайн та програмування', 'en' => 'Design and programming'],
                'description' => ['uk' => 'Розробка дизайну, програмування інтерактиву', 'en' => 'Design development, interactive programming'],
                'budget_planned' => 40000,
                'budget_actual' => 38000,
                'days_planned' => 20,
                'started_at' => now()->subDays(65),
                'completed_at' => now()->subDays(45),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Виготовлення та монтаж', 'en' => 'Manufacturing and installation'],
                'description' => ['uk' => 'Виготовлення конструкції, монтаж на локації', 'en' => 'Structure manufacturing, installation on location'],
                'budget_planned' => 80000,
                'budget_actual' => 82000,
                'days_planned' => 20,
                'started_at' => now()->subDays(45),
                'completed_at' => now()->subDays(30),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Запуск та налаштування', 'en' => 'Launch and tuning'],
                'description' => ['uk' => 'Фінальне тестування та запуск', 'en' => 'Final testing and launch'],
                'budget_planned' => 10000,
                'budget_actual' => 10000,
                'days_planned' => 5,
                'started_at' => now()->subDays(33),
                'completed_at' => now()->subDays(30),
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 1000,
                'title' => ['uk' => 'Фото інсталяції', 'en' => 'Installation photo'],
                'description' => ['uk' => 'Підписане фото інсталяції (А3)', 'en' => 'Signed photo of installation (A3)'],
                'quantity' => 100,
                'quantity_claimed' => 100,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 5000,
                'title' => ['uk' => 'Ексклюзивна екскурсія', 'en' => 'Exclusive tour'],
                'description' => ['uk' => 'Особиста екскурсія від автора з поясненнями роботи', 'en' => 'Personal tour from the author with explanations'],
                'quantity' => 20,
                'quantity_claimed' => 20,
            ]);
        }

        // Проєкт 15: Освітній онлайн-курс (Анна) - InProgress
        if ($anna && ! Project::where('slug', 'onlain-kurs-fotografii-z-nulia')->exists()) {
            $project = Project::create([
                'user_id' => $anna->id,
                'user_type' => UserType::Personal,
                'status' => \App\Enums\ProjectStatus::InProgress,
                'status_moderation' => \App\Enums\ModerationStatus::Approved,
                'slug' => 'onlain-kurs-fotografii-z-nulia',
                'title' => [
                    'uk' => 'Онлайн-курс "Фотографія з нуля"',
                    'en' => 'Online Course "Photography from Scratch"',
                ],
                'short_description' => [
                    'uk' => 'Практичний онлайн-курс для початківців з фотографії.',
                    'en' => 'Practical online course for photography beginners.',
                ],
                'cover' => ImageSeederHelper::getProjectCover('education'),
                'tags' => [
                    'uk' => ['онлайн-курс', 'фотографія', 'навчання'],
                    'en' => ['online course', 'photography', 'education'],
                ],
                'art_category_id' => ArtCategoryModel::resolveIdFromSlugs(ArtCategory::Visual->value, 'photography'),
                'currency' => Currency::UAH,
                'budget_goal' => 95000,
                'budget_collected' => 41000,
                'estimated_days' => 75,
                'budget_items' => [
                    ['name' => ['uk' => 'Підготовка програми курсу', 'en' => 'Course program preparation'], 'amount' => 15000],
                    ['name' => ['uk' => 'Зйомка відеоуроків', 'en' => 'Video lessons filming'], 'amount' => 30000],
                    ['name' => ['uk' => 'Монтаж та постпродакшн', 'en' => 'Editing and post-production'], 'amount' => 25000],
                    ['name' => ['uk' => 'Платформа для курсу', 'en' => 'Course platform'], 'amount' => 15000],
                    ['name' => ['uk' => 'Методичні матеріали', 'en' => 'Educational materials'], 'amount' => 10000],
                ],
                'content_blocks' => [
                    [
                        'type' => 'heading',
                        'heading_level' => 'h2',
                        'heading_text' => [
                            'uk' => 'Структура курсу',
                            'en' => 'Course structure',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Онлайн-курс "Фотографія з нуля" – це комплексна освітня програма для початківців, яка охоплює всі аспекти фотомистецтва. Курс складається з 20 відеоуроків загальною тривалістю 15 годин. Програма включає теорію (налаштування камери, експозиція, композиція, робота зі світлом) та практику (20 практичних завдань з детальним розбором). Студенти отримують довічний доступ до всіх матеріалів та можливість повторного перегляду.',
                            'en' => 'The online course "Photography from Scratch" is a comprehensive educational program for beginners that covers all aspects of photography. The course consists of 20 video lessons with a total duration of 15 hours. The program includes theory (camera settings, exposure, composition, working with light) and practice (20 practical assignments with detailed analysis). Students receive lifetime access to all materials and the ability to rewatch.',
                        ],
                    ],
                    [
                        'type' => 'image',
                        'image' => ImageSeederHelper::getProjectCover('education'),
                        'image_alt' => [
                            'uk' => 'Обкладинка онлайн-курсу "Фотографія з нуля" для початківців',
                            'en' => 'Cover of the online course "Photography from Scratch" for beginners',
                        ],
                        'image_caption' => [
                            'uk' => 'Комплексна освітня програма з 20 відеоуроків та 15 годин матеріалу',
                            'en' => 'Comprehensive educational program with 20 video lessons and 15 hours of material',
                        ],
                    ],
                    [
                        'type' => 'heading',
                        'heading_level' => 'h3',
                        'heading_text' => [
                            'uk' => 'Про викладача',
                            'en' => 'About the instructor',
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'paragraph_text' => [
                            'uk' => 'Курс веде Анна Павленко – професійний фотограф з 10-річним досвідом роботи. Анна спеціалізується на портретній та документальній фотографії, є лауреатом міжнародних фотоконкурсів. Її роботи публікувалися у провідних українських та міжнародних виданнях. У курсі Анна ділиться як технічними знаннями, так і творчими прийомами, що допомагають розвинути власний стиль.',
                            'en' => 'The course is taught by Anna Pavlenko – a professional photographer with 10 years of experience. Anna specializes in portrait and documentary photography, is a laureate of international photography competitions. Her work has been published in leading Ukrainian and international publications. In the course, Anna shares both technical knowledge and creative techniques that help develop one\'s own style.',
                        ],
                    ],
                ],
                'additional_info' => [
                    'uk' => 'Курс охоплює всі основи фотографії: налаштування камери, композиція, світло, обробка. Включає 20 практичних завдань з перевіркою. Автор - професійний фотограф з 10-річним досвідом Анна Павленко.',
                    'en' => 'The course covers all the basics of photography: camera settings, composition, light, processing. Includes 20 practical assignments with verification. Author - professional photographer with 10 years of experience Anna Pavlenko.',
                ],
                'announced_at' => now()->subDays(20),
                'planned_completion_at' => now()->addDays(55),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 1,
                'status' => \App\Enums\StageStatus::Completed,
                'title' => ['uk' => 'Програма курсу', 'en' => 'Course program'],
                'description' => ['uk' => 'Розробка програми та сценаріїв уроків', 'en' => 'Program and lesson scripts development'],
                'budget_planned' => 15000,
                'budget_actual' => 14000,
                'days_planned' => 15,
                'started_at' => now()->subDays(20),
                'completed_at' => now()->subDays(5),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 2,
                'status' => \App\Enums\StageStatus::InProgress,
                'title' => ['uk' => 'Зйомка уроків', 'en' => 'Filming lessons'],
                'description' => ['uk' => 'Зйомка та монтаж 20 відеоуроків', 'en' => 'Filming and editing 20 video lessons'],
                'budget_planned' => 55000,
                'days_planned' => 40,
                'started_at' => now()->subDays(5),
            ]);

            ProjectStage::create([
                'project_id' => $project->id,
                'order' => 3,
                'status' => \App\Enums\StageStatus::Planned,
                'title' => ['uk' => 'Запуск курсу', 'en' => 'Course launch'],
                'description' => ['uk' => 'Завантаження на платформу, тестування, запуск', 'en' => 'Platform upload, testing, launch'],
                'budget_planned' => 25000,
                'days_planned' => 20,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 1,
                'min_donation' => 500,
                'title' => ['uk' => 'Доступ до курсу', 'en' => 'Course access'],
                'description' => ['uk' => 'Довічний доступ до всіх уроків курсу', 'en' => 'Lifetime access to all course lessons'],
                'quantity' => null,
                'quantity_claimed' => 0,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 2,
                'min_donation' => 1500,
                'title' => ['uk' => 'Курс + зворотний зв\'язок', 'en' => 'Course + feedback'],
                'description' => ['uk' => 'Курс + перевірка 5 ваших робіт з коментарями', 'en' => 'Course + review of 5 your works with comments'],
                'quantity' => 100,
                'quantity_claimed' => 32,
            ]);

            ProjectBonus::create([
                'project_id' => $project->id,
                'order' => 3,
                'min_donation' => 5000,
                'title' => ['uk' => 'Премія пакет', 'en' => 'Premium package'],
                'description' => ['uk' => 'Курс + 3 індивідуальні консультації + сертифікат', 'en' => 'Course + 3 individual consultations + certificate'],
                'quantity' => 20,
                'quantity_claimed' => 5,
            ]);
        }
    }
}
