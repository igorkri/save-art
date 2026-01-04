<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
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
        if (! User::where('email', 'admin@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin->value,
                'email_verified_at' => now(),
            ]);
        }

        // Створюємо модератора
        if (! User::where('email', 'moderator@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Moderator',
                'email' => 'moderator@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Moderator->value,
                'email_verified_at' => now(),
            ]);
        }

        // Створюємо власників проєктів з профілями (якщо немає)
        $ownersCount = User::where('role', UserRole::Owner->value)->count();
        if ($ownersCount < 5) {
            User::factory()
                ->count(5 - $ownersCount)
                ->withProfiles()
                ->create([
                    'role' => UserRole::Owner->value,
                ]);
        }

        // Створюємо меценатів з профілями (якщо немає)
        $mecenatsCount = User::where('role', UserRole::Mecenat->value)->count();
        if ($mecenatsCount < 5) {
            User::factory()
                ->count(5 - $mecenatsCount)
                ->withProfiles()
                ->create([
                    'role' => UserRole::Mecenat->value,
                ]);
        }

        // Створюємо звичайних користувачів з профілями (якщо немає)
        $usersCount = User::where('role', UserRole::User->value)->count();
        if ($usersCount < 10) {
            User::factory()
                ->count(10 - $usersCount)
                ->withProfiles()
                ->create([
                    'role' => UserRole::User->value,
                ]);
        }
    }
}
