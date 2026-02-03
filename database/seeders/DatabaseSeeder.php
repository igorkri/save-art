<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Користувачі з профілями
        $this->call(UserSeeder::class);

        // Контент сторінок
        $this->call(ContentSeeder::class);

        // Головна сторінка
        $this->call(HomePageSeeder::class);

        // Налаштування сайту (Header/Footer)
        $this->call(SiteSettingsSeeder::class);

        // Сторінка "Про нас"
        $this->call(AboutSeeder::class);

        // Художня рада
        $this->call(ArtistBoardSeeder::class);

        // Категорії FAQ
        $this->call(FaqCategorySeeder::class);

        // FAQ питання та відповіді
        $this->call(FaqSeeder::class);

        // Проєкти з етапами та бонусами
        $this->call(ProjectSeeder::class);

        // Донати
        $this->call(DonationSeeder::class);

        // Повідомлення
        $this->call(MessageSeeder::class);

        // Звіти
        $this->call(ReportSeeder::class);

        // Сповіщення
        $this->call(NotificationSeeder::class);
    }
}
