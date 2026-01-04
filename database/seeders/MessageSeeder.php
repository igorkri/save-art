<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = User::whereIn('role', [UserRole::Admin->value, UserRole::Moderator->value])->get();
        $regularUsers = User::where('role', UserRole::User->value)->get();
        $owners = User::where('role', UserRole::Owner->value)->get();
        $projects = Project::all();

        if ($admins->isEmpty() || $regularUsers->isEmpty()) {
            return;
        }

        // Повідомлення від користувачів до адміністраторів
        foreach ($regularUsers->take(5) as $user) {
            Message::factory()
                ->count(2)
                ->fromUser()
                ->create([
                    'user_id' => $user->id,
                    'admin_id' => $admins->random()->id,
                ]);
        }

        // Повідомлення від адміністраторів до користувачів
        foreach ($regularUsers->take(3) as $user) {
            Message::factory()
                ->fromAdmin($admins->random())
                ->read()
                ->create([
                    'user_id' => $user->id,
                ]);
        }

        // Повідомлення пов'язані з проєктами
        foreach ($projects->take(3) as $project) {
            $owner = $owners->where('id', $project->user_id)->first() ?? $owners->random();

            // Питання від власника проєкту
            Message::factory()
                ->fromUser()
                ->create([
                    'user_id' => $owner->id,
                    'admin_id' => $admins->random()->id,
                    'project_id' => $project->id,
                    'subject' => 'Питання щодо проєкту: '.($project->title['uk'] ?? 'Без назви'),
                ]);

            // Відповідь від адміністратора
            Message::factory()
                ->fromAdmin($admins->random())
                ->create([
                    'user_id' => $owner->id,
                    'project_id' => $project->id,
                    'subject' => 'Re: Питання щодо проєкту',
                ]);
        }

        // Непрочитані повідомлення
        Message::factory()
            ->count(5)
            ->fromUser()
            ->unread()
            ->create([
                'admin_id' => $admins->random()->id,
            ]);
    }
}
