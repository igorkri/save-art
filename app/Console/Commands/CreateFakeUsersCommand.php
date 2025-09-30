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
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать фейковых пользователей через UserFactory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $withProfiles = $this->option('with-profiles');

        if ($count < 1) {
            $this->error('Количество пользователей должно быть больше нуля.');
            return self::FAILURE;
        }

        $factory = User::factory();
        if ($withProfiles) {
            $factory = $factory->withProfiles();
        }
        $users = $factory->count($count)->create();

        $this->info("Создано пользователей: {$users->count()}");
        if ($withProfiles) {
            $this->info('Для каждого пользователя созданы профили.');
        }
        return self::SUCCESS;
    }
}
