<?php

namespace Database\Factories;

use App\Enums\ContactInfoRequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactInfoRequest>
 */
class ContactInfoRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'owner_id' => User::factory(),
            'project_id' => null,
            'status' => ContactInfoRequestStatus::Pending,
            'decided_at' => null,
        ];
    }
}
