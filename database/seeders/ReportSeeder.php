<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Report;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();

        if ($projects->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            // Опублікований звіт
            Report::factory()
                ->for($project)
                ->for($project->user, 'user')
                ->create([
                    'status' => 'published',
                ]);

            // Для деяких проєктів додаємо чернетку звіту
            if (fake()->boolean(30)) {
                Report::factory()
                    ->draft()
                    ->for($project)
                    ->for($project->user, 'user')
                    ->create();
            }
        }
    }
}
