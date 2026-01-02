<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'title' => [
                'uk' => $this->faker->sentence(3),
                'en' => $this->faker->sentence(3),
            ],
            'description' => [
                'uk' => $this->faker->paragraphs(2, true),
                'en' => $this->faker->paragraphs(2, true),
            ],
            'cover' => '/assets/img/report-'.$this->faker->numberBetween(1, 5).'.webp',
            'images' => [
                '/assets/img/report-gallery-1.webp',
                '/assets/img/report-gallery-2.webp',
            ],
            'attachments' => null,
            'collected_amount' => $this->faker->randomFloat(2, 10000, 500000),
            'spent_amount' => $this->faker->randomFloat(2, 5000, 400000),
            'report_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => 'published',
        ];
    }

    /**
     * Вказати, що звіт є чернеткою
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}
