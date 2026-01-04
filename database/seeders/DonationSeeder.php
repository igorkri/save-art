<?php

namespace Database\Seeders;

use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class DonationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();
        $mecenats = User::where('role', UserRole::Mecenat->value)->get();
        $users = User::where('role', UserRole::User->value)->get();

        if ($projects->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            $bonuses = $project->bonuses ?? collect();

            // Оплачені донати від меценатів
            foreach ($mecenats->random(min(3, $mecenats->count())) as $mecenat) {
                Donation::factory()
                    ->paid()
                    ->for($project)
                    ->for($mecenat, 'user')
                    ->create([
                        'project_bonus_id' => $bonuses->isNotEmpty() ? $bonuses->random()->id : null,
                    ]);
            }

            // Донати від звичайних користувачів
            foreach ($users->random(min(2, $users->count())) as $user) {
                Donation::factory()
                    ->paid()
                    ->for($project)
                    ->for($user, 'user')
                    ->create();
            }

            // Анонімні донати
            Donation::factory()
                ->count(2)
                ->anonymous()
                ->paid()
                ->for($project)
                ->create();

            // Очікуючі донати
            Donation::factory()
                ->count(1)
                ->for($project)
                ->for($users->random(), 'user')
                ->create();

            // Невдалі донати
            Donation::factory()
                ->count(1)
                ->failed()
                ->for($project)
                ->create();
        }
    }
}
