<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectBonus;
use App\Models\ProjectStage;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Отримуємо власників проєктів
        $owners = User::where('role', UserRole::Owner->value)->get();

        if ($owners->isEmpty()) {
            $owners = User::factory()->count(3)->create(['role' => UserRole::Owner->value]);
        }

        // Створюємо чернетки проєктів
        foreach ($owners->take(2) as $owner) {
            $project = Project::factory()
                ->for($owner, 'user')
                ->create();

            // Додаємо етапи
            ProjectStage::factory()
                ->count(3)
                ->sequence(
                    ['order' => 1],
                    ['order' => 2],
                    ['order' => 3]
                )
                ->for($project)
                ->create();

            // Додаємо бонуси
            ProjectBonus::factory()
                ->count(3)
                ->sequence(
                    ['order' => 1, 'min_donation' => 100],
                    ['order' => 2, 'min_donation' => 500],
                    ['order' => 3, 'min_donation' => 1000]
                )
                ->for($project)
                ->create();
        }

        // Створюємо оголошені проєкти
        foreach ($owners->take(3) as $owner) {
            $project = Project::factory()
                ->for($owner, 'user')
                ->announced()
                ->create();

            ProjectStage::factory()
                ->count(4)
                ->sequence(
                    ['order' => 1],
                    ['order' => 2],
                    ['order' => 3],
                    ['order' => 4]
                )
                ->for($project)
                ->create();

            ProjectBonus::factory()
                ->count(4)
                ->sequence(
                    ['order' => 1, 'min_donation' => 50],
                    ['order' => 2, 'min_donation' => 200],
                    ['order' => 3, 'min_donation' => 500],
                    ['order' => 4, 'min_donation' => 1500]
                )
                ->for($project)
                ->create();
        }

        // Створюємо проєкти в процесі
        foreach ($owners->take(2) as $owner) {
            $project = Project::factory()
                ->for($owner, 'user')
                ->inProgress()
                ->create();

            ProjectStage::factory()->completed()->for($project)->create(['order' => 1]);
            ProjectStage::factory()->inProgress()->for($project)->create(['order' => 2]);
            ProjectStage::factory()->for($project)->create(['order' => 3]);

            ProjectBonus::factory()
                ->count(3)
                ->sequence(
                    ['order' => 1, 'min_donation' => 100, 'quantity_claimed' => 5],
                    ['order' => 2, 'min_donation' => 300, 'quantity_claimed' => 2],
                    ['order' => 3, 'min_donation' => 1000, 'quantity_claimed' => 1]
                )
                ->for($project)
                ->create();
        }

        // Створюємо завершені проєкти
        foreach ($owners->take(2) as $owner) {
            $project = Project::factory()
                ->for($owner, 'user')
                ->completed()
                ->create();

            ProjectStage::factory()
                ->count(3)
                ->completed()
                ->sequence(
                    ['order' => 1],
                    ['order' => 2],
                    ['order' => 3]
                )
                ->for($project)
                ->create();

            ProjectBonus::factory()
                ->count(2)
                ->exhausted()
                ->sequence(
                    ['order' => 1],
                    ['order' => 2]
                )
                ->for($project)
                ->create();
        }
    }
}
