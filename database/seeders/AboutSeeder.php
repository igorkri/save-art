<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\About;

class AboutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        About::create([
            'title' => [
                'en' => 'Project Tasks',
                'uk' => 'Задачі проєкту',
            ],
            'feats' => [
                'en' => [
                    [
                        'name' => 'Ecosystem',
                        'title' => 'An art platform for artists from Ukraine',
                        'description' => 'Scalable to other countries that need it.',
                    ],
                    [
                        'name' => 'People and Communication',
                        'title' => 'Connecting art creators with their admirers',
                        'description' => 'An opportunity to make a direct contribution to a specific direction and artist.',
                    ],
                    [
                        'name' => 'Co-authorship',
                        'title' => 'Patrons gain the opportunity to become co-authors',
                        'description' => 'You can take an active part in implementing art projects.',
                    ],
                ],
                'uk' => [
                    [
                        'name' => 'Екосистема',
                        'title' => 'Арт-платформа для митців з України',
                        'description' => 'Можливе масштабування на інші країни, які цього потребують.',
                    ],
                    [
                        'name' => 'Люди та комунікації',
                        'title' => 'Об’єднуємо людей мистецтва з його шанувальниками',
                        'description' => 'Можливість зробити безпосередній внесок у конкретний напрямок та для конкретного майстра.',
                    ],
                    [
                        'name' => 'Співавторство',
                        'title' => 'Меценати отримують можливість стати співавтором',
                        'description' => 'Можна прийняти безпосередню участь у реалізації арт-проєктів.',
                    ],
                ],
                'feat_image' => 'about/ccd2ec3d9f62c43369916fca22d338aeafb858e8_1760198320.webp',
            ],
            'description' => [
                'en' => 'to create the Future today.',
                'uk' => 'творити Майбутнє вже сьогодні.',
                'icon' => 'about/flag_ua_1760198320.webp',
                'text' => [
                    'en' => '<h5>Art is the only artifact that lives forever, reflecting the structure of the world through eras, events, and the emotional and sensory background of humanity amid historical turning points.</h5><p>The creative sphere is always socially vulnerable in the present, yet of immense importance in the perspective of eternity. Now is the time to save it, giving life to a better future.</p><p>The lack of support and funding for Ukraine’s art industry has caused a global creative crisis. Art is being preserved in bomb shelters, and while this issue is not being resolved at the state level, it can be addressed globally thanks to Rotary International clubs.</p>',
                    'uk' => '<h5>Мистецтво — єдиний артефакт, який живе вічно, відображаючи світобудову крізь епохи, події, емоційно-почуттєвий фон людства на фоні історичних переломів.</h5><p>Творча сфера завжди соціально незахищена в моменті, але надважлива у перспективі вічності, і саме зараз її необхідно рятувати, даруючи життя кращему майбутньому.</p><p>Відсутність підтримки та фінансування української арт-індустрії спричинила глобальну творчу кризу, мистецтво рятується в бомбосховищах, і наразі ця проблематика не вирішується на державному рівні, але може вирішитися на всесвітньому завдяки клубам Rotary International.</p>',
                ],
                'image' => 'about/invasion_1760198320.webp',
                'title_date' => ['en' => '24.02', 'uk' => '24.02'],
                'description_date' => [
                    'en' => 'Since the beginning of the full-scale invasion, Ukrainian culture has been under fire, and all artists are on the verge of survival.',
                    'uk' => 'З початком повномасштабного вторгнення українська культура перебуває під пострілами війни, а всі митці знаходяться на межі виживання.',
                ],
            ],
            'goals' => [
                'task' => [
                    'en' => 'Creation of an art platform to support Ukrainian artists',
                    'uk' => 'Створення арт-платформи підтримки українських митців',
                ],
                'image' => 'about/purpose_1760198321.webp',
                'title' => [
                    'en' => 'The Main Goal of the Project',
                    'uk' => 'Основна мета проєкту',
                ],
                'description' => [
                    'en' => '<p>to overcome the global cultural crisis in a country that stands as a guardian of peace worldwide...</p>',
                    'uk' => '<p>для подолання глобальної культурної кризи в країні, яка стоїть на варті миру у всьому світі...</p>',
                ],
            ],
            'tasks' => [
                'en' => [
                    ['task' => 'A platform for publishing a project'],
                    ['task' => 'Direct access to an audience willing to support'],
                    ['task' => 'Transparent funding model'],
                    ['task' => 'Support, connections, and growth opportunities'],
                ],
                'uk' => [
                    ['task' => 'Платформа для публікації проєкту'],
                    ['task' => 'Прямий доступ до аудиторії, яка хоче підтримати'],
                    ['task' => 'Прозора модель фінансування'],
                    ['task' => 'Супровід, зв’язки, можливості зростання'],
                ],
            ],
            'implementation' => [
                'image' => 'about/project_implementation_1760198321.webp',
                'title' => ['en' => 'Project Implementation', 'uk' => 'Реалізація проєкту'],
                'items' => [
                    'en' => [
                        ['item' => ['title' => 'Creation of a nationwide platform of individuals', 'description' => 'from various cultural fields...']],
                        ['item' => ['title' => 'Establishment of a grant program,', 'description' => 'which provides opportunities...']],
                        ['item' => ['title' => 'Creation of a nationwide catalog', 'description' => 'of Ukrainian artists...']],
                    ],
                    'uk' => [
                        ['item' => ['title' => 'Створення всеукраїнської платформи особистостей', 'description' => 'з різних культурних сфер діяльності...']],
                        ['item' => ['title' => 'Створення грантової програми,', 'description' => 'яка надає можливість...']],
                        ['item' => ['title' => 'Створення всеукраїнського каталогу', 'description' => 'українських митців...']],
                    ],
                ],
            ],
            'results' => [
                'title' => ['en' => 'Result', 'uk' => 'Результат'],
                'description' => [
                    'en' => '<h6>Artists receive grant support...</h6>',
                    'uk' => '<h6>Митці отримують грантову підтримку...</h6>',
                ],
            ],
            'id_art' => [
                'image' => 'about/idArtUa_1760198322.webp',
                'title' => ['en' => 'Charitable Foundation «ID Art UA»', 'uk' => 'Благодійний фонд «ID Art UA»'],
                'description' => [
                    'en' => '<p>To convey events, circumstances...</p>',
                    'uk' => '<p>Транслювати події, обставини...</p>',
                ],
            ],
            'events' => [
                'h2' => [
                    'en' => '<h2>Solo exhibitions of Ukrainian artists...</h2>',
                    'uk' => '<h2>Персональні виставки українських художників...</h2>',
                ],
                'title' => [
                    'en' => 'Pilot Project',
                    'uk' => 'Пілотний проект',
                ],
            ],
            'project' => [
                'image' => 'about/project/main_project_img_1760198323.webp',
                'image_bg' => 'about/project/main_project_bg_1760198322.webp',
                'description' => [
                    'en' => '<p>Ukraine\'s cultural heritage...</p>',
                    'uk' => '<p>Культурна спадщина України...</p>',
                ],
            ],
            'artists' => null,
            'is_active_artist' => 1,
            'partners' => null,
        ]);
    }
}
