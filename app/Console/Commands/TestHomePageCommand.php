<?php

namespace App\Console\Commands;

use App\Models\HomePage;
use Illuminate\Console\Command;

class TestHomePageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:homepage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test HomePage model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $homePage = HomePage::getActive();

        if ($homePage) {
            $this->info("Active HomePage ID: {$homePage->id}");
            $this->info('statistics_is_active: '.($homePage->statistics_is_active ? 'true' : 'false'));
        } else {
            $this->info('No active HomePage found');

            // Создаем тестовую запись
            $homePage = HomePage::create([
                'is_active' => true,
                'statistics_is_active' => false, // Установим в false для теста
                'hero_title' => 'Тест',
            ]);

            $this->info("Created test HomePage with ID: {$homePage->id}");
            $this->info('statistics_is_active: '.($homePage->statistics_is_active ? 'true' : 'false'));
        }

        return 0;
    }
}
