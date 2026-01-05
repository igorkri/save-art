<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Enums\SignService;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'template_version' => '1.0',
            'file_path' => 'contracts/'.fake()->uuid().'.pdf',
            'signed_file_path' => null,
            'status' => ContractStatus::Pending,
            'sign_service' => null,
            'signature_base64' => null,
            'signed_at' => null,
            'expires_at' => now()->addDays(30),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the contract is signed.
     */
    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Signed,
            'sign_service' => SignService::Diia,
            'signed_file_path' => 'contracts/signed/'.fake()->uuid().'.pdf',
            'signature_base64' => base64_encode(fake()->sha256()),
            'signed_at' => now(),
        ]);
    }

    /**
     * Indicate that the contract is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Rejected,
            'metadata' => ['rejection_reason' => fake()->sentence()],
        ]);
    }

    /**
     * Indicate that the contract is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Expired,
            'expires_at' => now()->subDays(1),
        ]);
    }
}
