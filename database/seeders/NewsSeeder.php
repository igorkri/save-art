<?php

namespace Database\Seeders;

use App\Enums\NewsCategory;
use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Пропускаємо якщо новини вже є
        if (News::exists()) {
            return;
        }

        $items = [
            [
                'slug' => 'vystavka-suchasnogo-mystetstva-cymy-vyhidnymy',
                'category' => NewsCategory::News,
                'published_at' => now()->subDays(1),
                'title' => [
                    'uk' => 'Виставка сучасного мистецтва відкриється вже цими вихідними',
                    'en' => 'A contemporary art exhibition opens this weekend',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                "У головній залі фонду завершили монтаж нової експозиції, яка об'єднала роботи 18 українських художників із шести міст.",
                                'Кураторська команда зосередилася на темі пам\'яті та стійкості, тому кожен зал має окремий наратив і супровідні тексти українською та англійською мовами.',
                            ],
                            'en' => [
                                "The foundation's main hall has finished installing a new exhibition that brings together works by 18 Ukrainian artists from six cities.",
                                'The curatorial team focused on memory and resilience, so each room has its own narrative with bilingual accompanying texts.',
                            ],
                        ],
                        'image' => null,
                    ],
                    [
                        'paragraphs' => [
                            'uk' => [
                                'На відкритті заплановані авторські екскурсії та публічна розмова про те, як мистецтво фіксує досвід воєнного часу.',
                                'Вхід у день відкриття безкоштовний за попередньою реєстрацією.',
                            ],
                            'en' => [
                                'The opening will feature guided tours by the artists and a public talk on how art captures the wartime experience.',
                                'Admission on the opening day is free with prior registration.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
            [
                'slug' => 'publichna-lekciya-pro-rol-kultury-v-misti',
                'category' => NewsCategory::Event,
                'published_at' => now()->subDays(2),
                'title' => [
                    'uk' => 'Публічна лекція про роль культури в міських трансформаціях',
                    'en' => 'Public lecture on the role of culture in urban transformation',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                'У межах освітньої програми фонду відбудеться лекція урбаністки Марії Козак про взаємодію культурних ініціатив і міського простору.',
                                'Спікерка розповість, як невеликі арт-проєкти змінюють локальні спільноти та створюють нові точки тяжіння в районах.',
                            ],
                            'en' => [
                                "As part of the foundation's educational programme, urbanist Maria Kozak will give a lecture on the interaction between cultural initiatives and urban space.",
                                'The speaker will explain how small art projects transform local communities and create new points of attraction in neighbourhoods.',
                            ],
                        ],
                        'image' => null,
                    ],
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Після лекції запланована панельна дискусія з архітекторами й менеджерами культурних інституцій.',
                                'Організатори відкриють вільний мікрофон для запитань від учасників.',
                            ],
                            'en' => [
                                'After the lecture, a panel discussion with architects and cultural institution managers is planned.',
                                'The organisers will open an open microphone for questions from attendees.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
            [
                'slug' => 'start-pryjomu-zayavok-na-tvorchu-rezydenciyu',
                'category' => NewsCategory::News,
                'published_at' => now()->subDays(3),
                'title' => [
                    'uk' => 'Стартував прийом заявок на участь у творчій резиденції',
                    'en' => 'Applications open for a new creative residency',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Фонд відкрив open call на двомісячну резиденцію для митців, які працюють із темами документування, пам\'яті та ідентичності.',
                                'У програму входять менторські сесії, доступ до майстерень і підтримка продакшну фінальних робіт.',
                            ],
                            'en' => [
                                'The foundation has launched an open call for a two-month residency for artists working on documentation, memory, and identity.',
                                'The programme includes mentoring sessions, workshop access, and production support for final works.',
                            ],
                        ],
                        'image' => null,
                    ],
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Пріоритет отримають проєкти з виразною соціальною складовою та потенціалом для публічної презентації.',
                                'Дедлайн подання заявок — 5 лютого 2026 року.',
                            ],
                            'en' => [
                                'Priority will be given to projects with a strong social component and potential for public presentation.',
                                'The application deadline is 5 February 2026.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
            [
                'slug' => 'onovlena-programa-zymovyh-podij-u-galereyi',
                'category' => NewsCategory::News,
                'published_at' => now()->subDays(4),
                'title' => [
                    'uk' => 'Оновлено програму зимових подій у галерейному просторі',
                    'en' => 'Winter events programme at the gallery has been updated',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Команда арт-простору опублікувала новий календар зимових подій із додатковими майстер-класами та відкритими читаннями.',
                                'У програмі з\'явилися вечірні формати для відвідувачів, які не можуть долучатися в будні вдень.',
                            ],
                            'en' => [
                                'The art space team has published a new calendar of winter events with additional workshops and open readings.',
                                'Evening formats have been added for visitors who cannot attend during weekday daytime.',
                            ],
                        ],
                        'image' => null,
                    ],
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Оновлений розклад уже доступний на сайті та в соціальних мережах ініціативи.',
                                'Для частини подій діє обмеження за кількістю місць, тому організатори радять реєструватися завчасно.',
                            ],
                            'en' => [
                                'The updated schedule is already available on the website and social media.',
                                'Some events have limited capacity, so organisers recommend registering in advance.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
            [
                'slug' => 'uchasnyky-predstavyly-seriyu-novyh-robit',
                'category' => NewsCategory::News,
                'published_at' => now()->subDays(5),
                'title' => [
                    'uk' => 'Учасники проєкту представили серію нових мистецьких робіт',
                    'en' => 'Project participants presented a new series of artworks',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Резиденти зимової лабораторії презентували фінальні роботи, створені за останні шість тижнів інтенсивної співпраці.',
                                'Серед представлених форматів — живопис, інсталяція, відеоарт і об\'єкти з перероблених матеріалів.',
                            ],
                            'en' => [
                                'Residents of the winter lab presented final works created over six weeks of intensive collaboration.',
                                'The formats on display include painting, installation, video art, and objects made from recycled materials.',
                            ],
                        ],
                        'image' => null,
                    ],
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Окрему увагу отримали проєкти, що працюють із темою переосмислення втрачених просторів.',
                                'Частина робіт поповнить мандрівну виставку фонду у 2026 році.',
                            ],
                            'en' => [
                                'Particular attention was drawn to projects reimagining lost spaces.',
                                'Some of the works will join the foundation\'s travelling exhibition in 2026.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
            [
                'slug' => 'dyskusiya-z-kuratoramy-pro-vystavkovi-koncepciyi',
                'category' => NewsCategory::Event,
                'published_at' => now()->subDays(6),
                'title' => [
                    'uk' => 'Дискусія з кураторками: як народжуються виставкові концепції',
                    'en' => 'Curators\' talk: how exhibition concepts are born',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Кураторки запрошують на відкриту розмову про процес формування виставкових концепцій — від ідеї до реалізації в просторі.',
                                'Учасники обговорять, як обирають авторів і будують діалог між роботами різних художників.',
                            ],
                            'en' => [
                                'Curators invite everyone to an open talk about how exhibition concepts take shape — from idea to spatial realisation.',
                                'Participants will discuss how artists are selected and how dialogue between different works is built.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
            [
                'slug' => 'blagodijnyj-aukcion-na-pidtrymku-mysteckyh-proektiv',
                'category' => NewsCategory::Event,
                'published_at' => now()->subDays(7),
                'title' => [
                    'uk' => 'Благодійний аукціон на підтримку мистецьких проєктів',
                    'en' => 'Charity auction in support of art projects',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Фонд організовує благодійний аукціон, кошти від якого підуть на реалізацію обраних мистецьких проєктів платформи.',
                                'У лотах — роботи митців-учасників платформи, надані спеціально для цієї події.',
                            ],
                            'en' => [
                                'The foundation is organising a charity auction, with proceeds going toward selected art projects on the platform.',
                                'The lots include works by participating artists, donated specifically for this event.',
                            ],
                        ],
                        'image' => null,
                    ],
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Реєстрація учасників триває на сайті фонду, кількість місць обмежена.',
                            ],
                            'en' => [
                                'Registration is open on the foundation\'s website; the number of seats is limited.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
            [
                'slug' => 'novyj-partnerskyj-proekt-iz-mizhnarodnoyu-galereyeyu',
                'category' => NewsCategory::News,
                'published_at' => now()->subDays(8),
                'title' => [
                    'uk' => 'Новий партнерський проєкт із міжнародною галереєю',
                    'en' => 'New partnership project with an international gallery',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Платформа оголосила про співпрацю з європейською галереєю, яка дозволить українським митцям презентувати роботи за кордоном.',
                                'Перша спільна виставка запланована на весну 2026 року.',
                            ],
                            'en' => [
                                'The platform has announced a partnership with a European gallery, allowing Ukrainian artists to present their work abroad.',
                                'The first joint exhibition is planned for spring 2026.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
            [
                'slug' => 'majster-klas-iz-restavraciyi-zhyvopysu',
                'category' => NewsCategory::Event,
                'published_at' => now()->subDays(9),
                'title' => [
                    'uk' => 'Майстер-клас із реставрації живопису для початківців',
                    'en' => 'Beginner workshop on painting restoration',
                ],
                'text_blocks' => [
                    [
                        'paragraphs' => [
                            'uk' => [
                                'Реставраторка з двадцятирічним досвідом проведе практичний майстер-клас про базові техніки збереження творів живопису.',
                                'Учасники отримають матеріали та зможуть попрактикуватися на навчальних зразках.',
                            ],
                            'en' => [
                                'A restorer with twenty years of experience will host a hands-on workshop on basic techniques for preserving paintings.',
                                'Participants will receive materials and practise on training samples.',
                            ],
                        ],
                        'image' => null,
                    ],
                ],
            ],
        ];

        foreach ($items as $item) {
            News::create([
                'title' => $item['title'],
                'slug' => $item['slug'],
                'category' => $item['category'],
                'published_at' => $item['published_at'],
                'main_image' => null,
                'text_blocks' => $item['text_blocks'],
                'is_active' => true,
            ]);
        }
    }
}
