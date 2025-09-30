<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

/**
 * Команда для создания фейковых пользователей через UserFactory
 *
 * php artisan user:create-fake 10 --with-profiles
 */
class CreateFakeUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-fake {count=1} {--with-profiles}';

    /**
     * Опис команди українською.
     *
     * @var string
     */
    protected $description = 'Створити фейкових користувачів через UserFactory';

    /**
     * Виконати консольну команду.
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $withProfiles = $this->option('with-profiles');

        if ($count < 1) {
            $this->error('Кількість користувачів має бути більше нуля.');
            return self::FAILURE;
        }

        $factory = User::factory();
        if ($withProfiles) {
            $factory = $factory->withProfiles();
        }
        $users = $factory->count($count)->create();

        $this->info("Створено користувачів: {$users->count()}");
        if ($withProfiles) {
            $this->info('Для кожного користувача створено профілі.');
        }
        return self::SUCCESS;
    }
}
