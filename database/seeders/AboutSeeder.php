<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AboutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\About::create([
            'title' => [
                'uk' => 'Задачі проєкту',
                'en' => 'Project Tasks'
            ],
            'feats' => [
                [
                    'name' => [
                        'uk' => 'Екосистема',
                        'en' => 'Ecosystem'
                    ],
                    'title' => [
                        'uk' => 'Арт-платформа для митців з України',
                        'en' => 'Art platform for artists from Ukraine'
                    ],
                    'description' => [
                        'uk' => 'Можливе масштабування на інші країни, які цього потребують.',
                        'en' => 'Possible scaling to other countries that need it.'
                    ]
                ],
                [
                    'name' => [
                        'uk' => 'Люди та комунікації',
                        'en' => 'People and Communications'
                    ],
                    'title' => [
                        'uk' => 'Об\'єднуємо людей мистецтва з його шанувальниками',
                        'en' => 'We unite people of art with its admirers'
                    ],
                    'description' => [
                        'uk' => 'Можливість зробити безпосередній внесок у конкретний напрямок та для конкретного майстра.',
                        'en' => 'Ability to make a direct contribution to a specific direction and for a specific master.'
                    ]
                ],
                [
                    'name' => [
                        'uk' => 'Співавторство',
                        'en' => 'Co-authorship'
                    ],
                    'title' => [
                        'uk' => 'Меценати отримують можливість стати співавтором',
                        'en' => 'Patrons get the opportunity to become co-authors'
                    ],
                    'description' => [
                        'uk' => 'Можна прийняти безпосередню участь у реалізації арт-проєктів.',
                        'en' => 'You can take direct part in the implementation of art projects.'
                    ]
                ]
            ],
            'description' => [
                'title_date' => [
                    'uk' => '24.02',
                    'en' => '24.02'
                ],
                'description_date' => [
                    'uk' => 'З початком повномасштабного вторгнення українська культура перебуває під пострілами війни, а всі митці знаходяться на межі виживання.',
                    'en' => 'Since the beginning of the full-scale invasion, Ukrainian culture has been under fire, and all artists are on the verge of survival.'
                ],
                'text' => [
                    'uk' => '<h5>Мистецтво — єдиний артефакт, який живе вічно, відображаючи світобудову крізь епохи, події, емоційно-почуттєвий фон людства на фоні історичних переломів.</h5><p>Творча сфера завжди соціально незахищена в моменті, але надважлива у перспективі вічності, і саме зараз її необхідно рятувати, даруючи життя кращому майбутньому.</p><p>Відсутність підтримки та фінансування української арт-індустрії спричинила глобальну творчу кризу, мистецтво рятується в бомбосховищах, і наразі ця проблематика не вирішується на державному рівні, але може вирішитися на всесвітньому завдяки клубам Rotary International.</p>',
                    'en' => '<h5>Art is the only artifact that lives forever, reflecting the worldview through epochs, events, and the emotional background of humanity against the backdrop of historical turning points.</h5><p>The creative sphere is always socially unprotected in the moment, but extremely important in the perspective of eternity, and right now it needs to be saved, giving life to a better future.</p><p>The lack of support and funding for the Ukrainian art industry has caused a global creative crisis, art is being saved in bomb shelters, and this problem is not currently being solved at the state level, but can be solved worldwide thanks to Rotary International clubs.</p>'
                ]
            ],
            'goals' => [
                'title' => [
                    'uk' => 'Основна мета проєкту',
                    'en' => 'Main Project Goal'
                ],
                'task' => [
                    'uk' => 'Створення арт-платформи підтримки українських митців',
                    'en' => 'Creating an art platform to support Ukrainian artists'
                ],
                'description' => [
                    'uk' => '<p>для подолання глобальної культурної кризи в країні, яка стоїть на варті миру у всьому світі, надання можливостей фіксувати історичний злам крізь призму художнього відображення сучасної реальності та формувати новітню мистецьку спадщину, яка транслює мистецтво перемоги та вбереже наступні покоління від руїн.</p>',
                    'en' => '<p>to overcome the global cultural crisis in a country that stands guard for peace throughout the world, providing opportunities to record historical breakthrough through the prism of artistic reflection of modern reality and form a modern artistic heritage that broadcasts the art of victory and will save future generations from ruins.</p>'
                ]
            ],
            'tasks' => [
                'title' => [
                    'uk' => 'Завдання платформи',
                    'en' => 'Platform Tasks'
                ],
                'description' => [
                    'uk' => 'Основні напрямки роботи платформи',
                    'en' => 'Main directions of platform work'
                ],
                'tasks' => [
                    [
                        'task' => [
                            'uk' => 'Платформа для публікації проєкту',
                            'en' => 'Platform for project publication'
                        ]
                    ],
                    [
                        'task' => [
                            'uk' => 'Прямий доступ до аудиторії, яка хоче підтримати',
                            'en' => 'Direct access to an audience that wants to support'
                        ]
                    ],
                    [
                        'task' => [
                            'uk' => 'Прозора модель фінансування',
                            'en' => 'Transparent funding model'
                        ]
                    ],
                    [
                        'task' => [
                            'uk' => 'Супровід, зв\'язки, можливості зростання',
                            'en' => 'Support, connections, growth opportunities'
                        ]
                    ]
                ]
            ],
            'implementation' => [
                'title' => [
                    'uk' => 'Реалізація проєкту',
                    'en' => 'Project Implementation'
                ],
                'items' => [
                    [
                        'item' => [
                            'title' => [
                                'uk' => 'Створення всеукраїнської платформи особистостей',
                                'en' => 'Creating a nationwide platform of personalities'
                            ],
                            'description' => [
                                'uk' => 'з різних культурних сфер діяльності, чия професійна кваліфікація передбачає високий творчий потенціал для створення унікальних витворів мистецтва, що художньо переосмислюють сучасні історичні події та стани людства, пропагують мир, збагачують світову мистецьку спадщину.',
                                'en' => 'from various cultural spheres of activity, whose professional qualifications involve high creative potential for creating unique works of art that artistically rethink modern historical events and states of humanity, promote peace, enrich world artistic heritage.'
                            ]
                        ]
                    ],
                    [
                        'item' => [
                            'title' => [
                                'uk' => 'Створення грантової програми,',
                                'en' => 'Creating a grant program,'
                            ],
                            'description' => [
                                'uk' => 'яка надає можливість втілення мистецьких задумів у реалізований проект шляхом відбору заявок створеною спеціальною експертною комісією та розподілу фінансування серед відібраних проектів, з повною прозорістю безготівкової звітності меценатам в процесі реалізації програми.',
                                'en' => 'which provides the opportunity to implement artistic ideas into a realized project by selecting applications by a specially created expert commission and distributing funding among selected projects, with complete transparency of non-cash reporting to patrons during the program implementation process.'
                            ]
                        ]
                    ],
                    [
                        'item' => [
                            'title' => [
                                'uk' => 'Створення всеукраїнського каталогу',
                                'en' => 'Creating a nationwide catalog'
                            ],
                            'description' => [
                                'uk' => 'українських митців та фізичних витворів образотворчого мистецтва з подальшим його розповсюдженням на онлайн та офлайн медіаресурсах.',
                                'en' => 'of Ukrainian artists and physical works of fine art with its further distribution on online and offline media resources.'
                            ]
                        ]
                    ]
                ]
            ],
            'results' => [
                'title' => [
                    'uk' => 'Результат',
                    'en' => 'Result'
                ],
                'description' => [
                    'uk' => '<h6>Митці отримують грантову підтримку власних проєктів</h6><h6>Меценати отримують можливість співавторства новітнього витвору мистецтва історичної культурної спадщини</h6><h6>Світ отримує актуальні витвори мистецтва з епіцентру історичних подій</h6><hr><h3>Рятуючи мистецтво — будуєш майбутнє!</h3>',
                    'en' => '<h6>Artists receive grant support for their own projects</h6><h6>Patrons get the opportunity to co-author the latest work of art of historical cultural heritage</h6><h6>The world receives relevant works of art from the epicenter of historical events</h6><hr><h3>By saving art - you build the future!</h3>'
                ]
            ],
            'id_art' => [
                'title' => [
                    'uk' => 'Благодійний фонд «ID Art UA»',
                    'en' => 'Charitable Foundation "ID Art UA"'
                ],
                'description' => [
                    'uk' => '<p>Транслювати події, обставини, відчуття, почуття та емоції — крізь художні світи, що відображають світ реальний. Світ, що твориться зараз знов — руками і серцями кожної небайдужої Людини.</p><p>Ідентифікувати сучасну Україну очима Мистецтва — надзавдання громадської та благодійної організацій ID_Art UA.</p><p>Об\' єднувати митців, створюючи платформу для творчого втілення справжніх Талантів, яких війна змусила мовчати… Адже художнє слово здатне наблизити мир!</p>',
                    'en' => '<p>To broadcast events, circumstances, feelings, emotions through artistic worlds that reflect the real world. A world that is being created again now - by the hands and hearts of every caring Person.</p><p>To identify modern Ukraine through the eyes of Art is the main task of the public and charitable organizations ID_Art UA.</p><p>To unite artists by creating a platform for the creative embodiment of true Talents whom the war forced to remain silent... After all, the artistic word can bring peace closer!</p>'
                ]
            ],
            'events' => [
                'title' => [
                    'uk' => 'Пілотний проект',
                    'en' => 'Pilot Project'
                ],
                'h2' => [
                    'uk' => '<h2>Персональні виставки українських художників в провідних музеях світу</h2>',
                    'en' => '<h2>Personal exhibitions of Ukrainian artists in leading museums of the world</h2>'
                ]
            ],
            'project' => [
                'description' => [
                    'uk' => '<p>Культурна спадщина України в контексті нових історичних подій набула особливої актуальності та нових змістів.</p><p>Сьогодні образотворче мистецтво у фарбах на холсті відображає не просто сюжети чи метафори, а небувалий у сучасній історії злам епох. Художники фіксують не тільки події, а ще й глибину емоційно-почуттєвого фону, який неможливо передати на словах та в стрічці новин. Це - новітнє мистецтво, сучасне, переосмислене, глибинне, на віки.</p><p>Саме зараз настає його час - аби уберегти наступні покоління від руїн, транслюючи біль крізь художні образи.</p><h6>Це те, що важливо не просто знати, а ще усвідомлювати, відчувати, формувати ставлення, запобігати та унеможливлювати в теперішньому та майбутньому.</h6><h6>Це те, що важливо не тільки дивитися, а ще і бачити.</h6><h6>Артефактами.</h6><h6>Творіннями найкращих українських художників.</h6><h6>В найкращих музеях світу.</h6>',
                    'en' => '<p>Ukraine\'s cultural heritage in the context of new historical events has acquired special relevance and new meanings.</p><p>Today, fine art in paints on canvas reflects not just plots or metaphors, but an unprecedented break of epochs in modern history. Artists record not only events, but also the depth of the emotional background, which is impossible to convey in words and in the news feed. This is modern art, contemporary, rethought, deep, for ages.</p><p>Right now is its time - to protect future generations from ruins by broadcasting pain through artistic images.</p><h6>This is what is important not just to know, but also to realize, feel, form attitudes, prevent and make impossible in the present and future.</h6><h6>This is what is important not only to look at, but also to see.</h6><h6>With artifacts.</h6><h6>Creations of the best Ukrainian artists.</h6><h6>In the best museums of the world.</h6>'
                ]
            ],
            'artists' => [
                'uk' => 'Українські митці',
                'en' => 'Ukrainian artists'
            ],
            'partners' => [
                'uk' => 'Наші партнери: art-ua.com, art-ua.info та інші платформи підтримки мистецтва',
                'en' => 'Our partners: art-ua.com, art-ua.info and other art support platforms'
            ],
            'is_active_artist' => true
        ]);
    }
}
