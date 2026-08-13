<?php

namespace Database\Seeders;

use App\Enums\ProfileType;
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
                'full_name' => 'Адміністратор SaveArt',
                'slug' => 'admin-saveart',
                'email' => 'admin@saveart.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin->value,
                'email_verified_at' => now(),
            ]);

            \App\Models\ProfileLegal::factory()->create(['user_id' => $admin->id]);
            \App\Models\ProfileSocial::factory()->create(['user_id' => $admin->id]);
        }

        // Створюємо модератора
        if (! User::where('email', 'moderator@saveart.com')->exists()) {
            $moderator = User::factory()->create([
                'slug' => 'iryna-koval',
                'email' => 'moderator@saveart.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Moderator->value,
                'email_verified_at' => now(),
                'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                'full_name' => 'Ірина Коваль',
                'profession' => 'Модератор платформи',
                'city' => 'Київ',
            ]);

            \App\Models\ProfileLegal::factory()->create(['user_id' => $moderator->id]);
            \App\Models\ProfileSocial::factory()->create(['user_id' => $moderator->id]);
        }

        // Реалістичні власники проєктів (митці)
        $artists = [
            [

                'slug' => 'oksana-petrenko',
                'email' => 'oksana.petrenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                    'full_name' => 'Оксана Петренко',
                    'profession' => 'Художниця, майстриня портретного живопису',
                    'country' => 'Україна',
                    'region' => 'Львівська область',
                    'city' => 'Львів',
                    'description' => 'Займаюсь живописом більше 15 років. Спеціалізуюсь на портретах та композиціях на теми української історії та культури.',
                    'tags' => 'живопис, портрети, українська культура',
                ],
            ],
            [

                'slug' => 'taras-kovalenko',
                'email' => 'taras.kovalenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('man'),
                    'full_name' => 'Тарас Коваленко',
                    'profession' => 'Скульптор, монументаліст',
                    'country' => 'Україна',
                    'region' => 'Київська область',
                    'city' => 'Київ',
                    'description' => 'Створюю скульптури та монументи, що відображають історичні події та героїв України. Працюю з бронзою, камінем та сучасними матеріалами.',
                    'tags' => 'скульптура, монументалізм, бронза',
                ],
            ],
            [

                'slug' => 'maria-shevchenko',
                'email' => 'maria.shevchenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                    'full_name' => 'Марія Шевченко',
                    'profession' => 'Театральна режисерка',
                    'country' => 'Україна',
                    'region' => 'Одеська область',
                    'city' => 'Одеса',
                    'description' => 'Режисую сучасні театральні постановки, що торкаються актуальних соціальних тем та історії України. Поєдную класику з новітніми театральними формами.',
                    'tags' => 'театр, режисура, сучасна драма',
                ],
            ],
            [

                'slug' => 'dmytro-lytvyn',
                'email' => 'dmytro.lytvyn@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('man'),
                    'full_name' => 'Дмитро Литвин',
                    'profession' => 'Музикант, композитор',
                    'country' => 'Україна',
                    'region' => 'Харківська область',
                    'city' => 'Харків',
                    'description' => 'Створюю музику на перетині традицій та сучасності. Працюю з українськими народними мелодіями, адаптуючи їх до сучасного звучання.',
                    'tags' => 'музика, композиція, фолк',
                ],
            ],
            [

                'slug' => 'anna-pavlenko',
                'email' => 'anna.pavlenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                    'full_name' => 'Анна Павленко',
                    'profession' => 'Фотографка-документалістка',
                    'country' => 'Україна',
                    'region' => 'Дніпропетровська область',
                    'city' => 'Дніпро',
                    'description' => 'Документую життя українців у різних регіонах країни, фіксую історичні події та портрети сучасників. Мої роботи публікувались у провідних виданнях.',
                    'tags' => 'фотографія, документалістика, портрет',
                ],
            ],
        ];

        foreach ($artists as $artistData) {
            if (! User::where('email', $artistData['email'])->exists()) {
                $user = User::factory()->create(array_merge([
                    'slug' => $artistData['slug'],
                    'email' => $artistData['email'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::User->value,
                    'profile_type' => ProfileType::Artist->value,
                    'email_verified_at' => now(),
                ], $artistData['profile']));

                \App\Models\ProfileLegal::factory()->create(['user_id' => $user->id]);
                \App\Models\ProfileSocial::factory()->create(['user_id' => $user->id]);
            }
        }

        // Реалістичні меценати
        $mecenats = [
            [

                'slug' => 'petro-vasylenko',
                'email' => 'petro.vasylenko@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('man'),
                    'full_name' => 'Петро Василенко',
                    'profession' => 'Підприємець, меценат',
                    'country' => 'Україна',
                    'region' => 'Київська область',
                    'city' => 'Київ',
                    'description' => 'Підтримую українське мистецтво та культуру. Вірю, що через мистецтво ми зберігаємо нашу ідентичність.',
                ],
            ],
            [

                'slug' => 'olena-melnyk',
                'email' => 'olena.melnyk@example.com',
                'profile' => [
                    'avatar' => ImageSeederHelper::getUserAvatar('woman'),
                    'full_name' => 'Олена Мельник',
                    'profession' => 'Колекціонер, благодійниця',
                    'country' => 'Україна',
                    'region' => 'Львівська область',
                    'city' => 'Львів',
                    'description' => 'Колекціоную сучасне українське мистецтво. Регулярно підтримую талановитих митців на початку їхнього шляху.',
                ],
            ],
        ];

        foreach ($mecenats as $mecenatData) {
            if (! User::where('email', $mecenatData['email'])->exists()) {
                $user = User::factory()->create(array_merge([
                    'slug' => $mecenatData['slug'],
                    'email' => $mecenatData['email'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::User->value,
                    'profile_type' => ProfileType::Patron->value,
                    'email_verified_at' => now(),
                ], $mecenatData['profile']));

                \App\Models\ProfileLegal::factory()->create(['user_id' => $user->id]);
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
