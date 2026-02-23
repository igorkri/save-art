<?php

namespace App\Console\Commands;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Report;
use Illuminate\Console\Command;

/**
 * Команда для генерації звітів з проєктів
 *
 * php artisan reports:generate
 * php artisan reports:generate --status=completed
 * php artisan reports:generate --project=5
 * php artisan reports:generate --all
 */
class GenerateReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate
                            {--project= : ID конкретного проєкту}
                            {--status= : Статус проєктів (completed, fundraising, announced)}
                            {--all : Для всіх проєктів без звітів}
                            {--force : Створити звіти навіть якщо вони вже є}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерувати звіти з проєктів';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->option('project');
        $status = $this->option('status');
        $all = $this->option('all');
        $force = $this->option('force');

        if (! $projectId && ! $status && ! $all) {
            $this->error('Вкажіть одну з опцій: --project=ID, --status=STATUS, або --all');
            $this->line('Приклади:');
            $this->line('  php artisan reports:generate --project=5');
            $this->line('  php artisan reports:generate --status=completed');
            $this->line('  php artisan reports:generate --all');

            return self::FAILURE;
        }

        $query = Project::query()->with('user');

        // Фільтр по конкретному проєкту
        if ($projectId) {
            $query->where('id', $projectId);
        }

        // Фільтр по статусу
        if ($status) {
            $projectStatus = ProjectStatus::tryFrom($status);
            if (! $projectStatus) {
                $this->error("Невідомий статус: {$status}");
                $this->line('Доступні статуси: '.implode(', ', array_column(ProjectStatus::cases(), 'value')));

                return self::FAILURE;
            }
            $query->where('status', $projectStatus);
        }

        // Якщо не force — виключаємо проєкти, що вже мають опублікований звіт
        if (! $force) {
            $query->whereDoesntHave('reports', function ($q) {
                $q->where('status', 'published');
            });
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->info('Проєктів для генерації звітів не знайдено.');

            return self::SUCCESS;
        }

        $this->info("Знайдено проєктів: {$projects->count()}");

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        $createdCount = 0;

        foreach ($projects as $project) {
            $report = $this->createOrUpdateReportForProject($project);
            if ($report) {
                $createdCount++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Опрацьовано звітів: {$createdCount}");

        return self::SUCCESS;
    }

    /**
     * Создать или обновить отчет для проекта
     */
    private function createOrUpdateReportForProject(Project $project): ?Report
    {
        $report = Report::firstOrNew([
            'project_id' => $project->id,
        ]);

        $report->fill([
            'user_id' => $project->user_id,
            'title' => [
                'uk' => $project->title['uk'] ?? '',
                'en' => $project->title['en'] ?? '',
            ],
            'description' => [
                'uk' => $this->generateDescriptionUk($project),
                'en' => $this->generateDescriptionEn($project),
            ],
            'cover' => $project->cover,
            'images' => null,
            'attachments' => null,
            'collected_amount' => $project->budget_collected ?? 0,
            'goal_amount' => $project->budget_goal ?? 0,
            'spent_amount' => 0,
            'report_date' => now(),
            'status' => $report->exists ? $report->status : 'draft',
        ]);

        $report->save();

        return $report;
    }

    /**
     * Генерувати опис українською
     */
    private function generateDescriptionUk(Project $project): string
    {
        $description = $project->short_description['uk'] ?? '';

        return "Автоматично згенерований звіт для проєкту.\n\n"
            ."Зібрана сума: {$project->budget_collected} грн\n"
            ."Ціль збору: {$project->budget_goal} грн\n\n"
            .($description ? "Опис проєкту:\n{$description}" : '');
    }

    /**
     * Генерувати опис англійською
     */
    private function generateDescriptionEn(Project $project): string
    {
        $description = $project->short_description['en'] ?? '';

        return "Automatically generated report for the project.\n\n"
            ."Collected amount: {$project->budget_collected} UAH\n"
            ."Funding goal: {$project->budget_goal} UAH\n\n"
            .($description ? "Project description:\n{$description}" : '');
    }
}
