<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Database\Seeders\Helpers\ImageSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Створюємо адміністратора
        if (! User::where('email', 'admin@saveart.com')->exists()) {
            $admin = User::factory()->create([
                'name' => 'Адміністратор SaveArt',
                'slug' => 'admin-saveart',
                'email' => 'admin@saveart.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin->value,
                'email_verified_at' => now(),
            ]);
        }

        // Створюємо модератора
        if (! User::where('email', 'moderator@saveart.com')->exists()) {
            $moderator = User::factory()->create([
                'name' => 'Ірина Коваль',
                'slug' => 'iryna-koval',
                'email' => 'moderator@saveart.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Moderator->value,
                'email_verified_at' => now(),
            ]);

            \App\Models\ProfilePersonal::create([
                'user_id' => $moderator->id,
                'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                'full_name' => [
                    'uk' => 'Ірина Коваль',
                    'en' => 'Iryna Koval',
                ],
                'profession' => [
                    'uk' => 'Модератор платформи',
                    'en' => 'Platform Moderator',
                ],
                'city' => [
                    'uk' => 'Київ',
                    'en' => 'Kyiv',
                ],
            ]);
        }

        // Реалістичні власники проєктів (митці)
        $artists = [
            [
                'name' => 'Оксана Петренко',
                'slug' => 'oksana-petrenko',
                'email' => 'oksana.petrenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                    'full_name' => ['uk' => 'Оксана Петренко', 'en' => 'Oksana Petrenko'],
                    'profession' => ['uk' => 'Художниця, майстриня портретного живопису', 'en' => 'Artist, Portrait Painter'],
                    'city' => ['uk' => 'Львів', 'en' => 'Lviv'],
                    'description' => [
                        'uk' => 'Займаюсь живописом більше 15 років. Спеціалізуюсь на портретах та композиціях на теми української історії та культури.',
                        'en' => 'I have been painting for over 15 years. I specialize in portraits and compositions on themes of Ukrainian history and culture.',
                    ],
                    'tags' => ['uk' => 'живопис, портрети, українська культура', 'en' => 'painting, portraits, Ukrainian culture'],
                ],
            ],
            [
                'name' => 'Тарас Коваленко',
                'slug' => 'taras-kovalenko',
                'email' => 'taras.kovalenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('man'),
                    'full_name' => ['uk' => 'Тарас Коваленко', 'en' => 'Taras Kovalenko'],
                    'profession' => ['uk' => 'Скульптор, монументаліст', 'en' => 'Sculptor, Monumentalist'],
                    'city' => ['uk' => 'Київ', 'en' => 'Kyiv'],
                    'description' => [
                        'uk' => 'Створюю скульптури та монументи, що відображають історичні події та героїв України. Працюю з бронзою, камінем та сучасними матеріалами.',
                        'en' => 'I create sculptures and monuments reflecting historical events and heroes of Ukraine. I work with bronze, stone and modern materials.',
                    ],
                    'tags' => ['uk' => 'скульптура, монументалізм, бронза', 'en' => 'sculpture, monumentalism, bronze'],
                ],
            ],
            [
                'name' => 'Марія Шевченко',
                'slug' => 'maria-shevchenko',
                'email' => 'maria.shevchenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                    'full_name' => ['uk' => 'Марія Шевченко', 'en' => 'Maria Shevchenko'],
                    'profession' => ['uk' => 'Театральна режисерка', 'en' => 'Theatre Director'],
                    'city' => ['uk' => 'Одеса', 'en' => 'Odesa'],
                    'description' => [
                        'uk' => 'Режисую сучасні театральні постановки, що торкаються актуальних соціальних тем та історії України. Поєдную класику з новітніми театральними формами.',
                        'en' => 'I direct modern theatrical productions touching on current social themes and Ukrainian history. I combine classics with innovative theatrical forms.',
                    ],
                    'tags' => ['uk' => 'театр, режисура, сучасна драма', 'en' => 'theatre, directing, modern drama'],
                ],
            ],
            [
                'name' => 'Дмитро Литвин',
                'slug' => 'dmytro-lytvyn',
                'email' => 'dmytro.lytvyn@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('man'),
                    'full_name' => ['uk' => 'Дмитро Литвин', 'en' => 'Dmytro Lytvyn'],
                    'profession' => ['uk' => 'Музикант, композитор', 'en' => 'Musician, Composer'],
                    'city' => ['uk' => 'Харків', 'en' => 'Kharkiv'],
                    'description' => [
                        'uk' => 'Створюю музику на перетині традицій та сучасності. Працюю з українськими народними мелодіями, адаптуючи їх до сучасного звучання.',
                        'en' => 'I create music at the intersection of tradition and modernity. I work with Ukrainian folk melodies, adapting them to contemporary sound.',
                    ],
                    'tags' => ['uk' => 'музика, композиція, фолк', 'en' => 'music, composition, folk'],
                ],
            ],
            [
                'name' => 'Анна Павленко',
                'slug' => 'anna-pavlenko',
                'email' => 'anna.pavlenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                    'full_name' => ['uk' => 'Анна Павленко', 'en' => 'Anna Pavlenko'],
                    'profession' => ['uk' => 'Фотографка-документалістка', 'en' => 'Documentary Photographer'],
                    'city' => ['uk' => 'Дніпро', 'en' => 'Dnipro'],
                    'description' => [
                        'uk' => 'Документую життя українців у різних регіонах країни, фіксую історичні події та портрети сучасників. Мої роботи публікувались у провідних виданнях.',
                        'en' => 'I document the lives of Ukrainians in different regions of the country, capturing historical events and portraits of contemporaries. My work has been published in leading publications.',
                    ],
                    'tags' => ['uk' => 'фотографія, документалістика, портрет', 'en' => 'photography, documentary, portrait'],
                ],
            ],
        ];

        foreach ($artists as $artistData) {
            if (! User::where('email', $artistData['email'])->exists()) {
                $user = User::factory()->create([
                    'name' => $artistData['name'],
                    'slug' => $artistData['slug'],
                    'email' => $artistData['email'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::Owner->value,
                    'email_verified_at' => now(),
                ]);

                \App\Models\ProfilePersonal::create(array_merge(
                    ['user_id' => $user->id],
                    $artistData['profile']
                ));

                \App\Models\ProfileLegal::factory()->create(['user_id' => $user->id]);
                \App\Models\ProfileSocial::factory()->create(['user_id' => $user->id]);
            }
        }

        // Реалістичні меценати
        $mecenats = [
            [
                'name' => 'Петро Василенко',
                'slug' => 'petro-vasylenko',
                'email' => 'petro.vasylenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('man'),
                    'full_name' => ['uk' => 'Петро Василенко', 'en' => 'Petro Vasylenko'],
                    'profession' => ['uk' => 'Підприємець, меценат', 'en' => 'Entrepreneur, Patron'],
                    'city' => ['uk' => 'Київ', 'en' => 'Kyiv'],
                    'description' => [
                        'uk' => 'Підтримую українське мистецтво та культуру. Вірю, що через мистецтво ми зберігаємо нашу ідентичність.',
                        'en' => 'I support Ukrainian art and culture. I believe that through art we preserve our identity.',
                    ],
                ],
            ],
            [
                'name' => 'Олена Мельник',
                'slug' => 'olena-melnyk',
                'email' => 'olena.melnyk@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                    'full_name' => ['uk' => 'Олена Мельник', 'en' => 'Olena Melnyk'],
                    'profession' => ['uk' => 'Колекціонер, благодійниця', 'en' => 'Collector, Philanthropist'],
                    'city' => ['uk' => 'Львів', 'en' => 'Lviv'],
                    'description' => [
                        'uk' => 'Колекціоную сучасне українське мистецтво. Регулярно підтримую талановитих митців на початку їхнього шляху.',
                        'en' => 'I collect contemporary Ukrainian art. I regularly support talented artists at the beginning of their journey.',
                    ],
                ],
            ],
        ];

        foreach ($mecenats as $mecenatData) {
            if (! User::where('email', $mecenatData['email'])->exists()) {
                $user = User::factory()->create([
                    'name' => $mecenatData['name'],
                    'slug' => $mecenatData['slug'],
                    'email' => $mecenatData['email'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::Mecenat->value,
                    'email_verified_at' => now(),
                ]);

                \App\Models\ProfilePersonal::create(array_merge(
                    ['user_id' => $user->id],
                    $mecenatData['profile']
                ));

                \App\Models\ProfileSocial::factory()->create(['user_id' => $user->id]);
            }
        }

        // Додаємо ще кілька звичайних користувачів
        $usersCount = User::where('role', UserRole::User->value)->count();
        if ($usersCount < 3) {
            User::factory()
                ->count(3 - $usersCount)
                ->withProfiles()
                ->create([
                    'role' => UserRole::User->value,
                ]);
        }
    }
}
