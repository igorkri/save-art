<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNotIn('role', [UserRole::Developer->value])->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users->take(10) as $user) {
            // Створюємо різні типи сповіщень
            Notification::factory()
                ->forUser($user)
                ->donation()
                ->create();

            Notification::factory()
                ->forUser($user)
                ->moderation()
                ->create();

            // Прочитані сповіщення
            Notification::factory()
                ->forUser($user)
                ->read()
                ->create();

            // Непрочитані сповіщення
            Notification::factory()
                ->count(2)
                ->forUser($user)
                ->create();
        }
    }
}
