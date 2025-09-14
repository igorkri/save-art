<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Создаем пользователей с разными ролями для тестирования
        User::factory()->developer()->create([
            'name' => 'Developer User',
            'email' => 'developer@example.com',
        ]);

        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->moderator()->create([
            'name' => 'Moderator User',
            'email' => 'moderator@example.com',
        ]);

        User::factory()->owner()->create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
        ]);

        User::factory()->mecenat()->create([
            'name' => 'Mecenat User',
            'email' => 'mecenat@example.com',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => UserRole::User,
        ]);

        // Создаем несколько обычных пользователей
        User::factory(5)->create();
    }
}
