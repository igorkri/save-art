<?php

namespace Tests\Unit\Services;

use App\Enums\DonationStatus;
use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Models\User;
use App\Services\DonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DonationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DonationService::class);
    }

    public function test_process_paid_donation_updates_status(): void
    {
        $project = Project::factory()->create([
            'budget_collected' => 0,
            'donors_count' => 0,
        ]);
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'status' => DonationStatus::Pending,
            'amount' => 1000,
        ]);

        $this->service->processPaidDonation($donation);

        $donation->refresh();
        $project->refresh();

        $this->assertEquals('paid', $donation->status->value ?? $donation->status);
        $this->assertNotNull($donation->paid_at);
        $this->assertEquals(1000, $project->budget_collected);
        $this->assertEquals(1, $project->donors_count);
    }

    public function test_process_paid_donation_increments_project_budget(): void
    {
        $project = Project::factory()->create([
            'budget_collected' => 5000,
            'donors_count' => 3,
        ]);
        $user = User::factory()->create();
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => DonationStatus::Pending,
            'amount' => 2500,
        ]);

        $this->service->processPaidDonation($donation);

        $project->refresh();
        $this->assertEquals(7500, $project->budget_collected);
    }

    public function test_process_paid_donation_does_not_double_count_donors(): void
    {
        $project = Project::factory()->create([
            'budget_collected' => 0,
            'donors_count' => 0,
        ]);
        $user = User::factory()->create();

        // First donation
        $donation1 = Donation::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => DonationStatus::Pending,
            'amount' => 500,
        ]);
        $this->service->processPaidDonation($donation1);

        // Second donation from same user
        $donation2 = Donation::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => DonationStatus::Pending,
            'amount' => 300,
        ]);
        $this->service->processPaidDonation($donation2);

        $project->refresh();
        $this->assertEquals(800, $project->budget_collected);
        $this->assertEquals(1, $project->donors_count); // Still 1, not 2
    }

    public function test_process_paid_donation_with_bonus_increments_claimed(): void
    {
        $project = Project::factory()->create();
        $bonus = ProjectBonus::factory()->create([
            'project_id' => $project->id,
            'quantity' => 10,
            'quantity_claimed' => 2,
        ]);
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'project_bonus_id' => $bonus->id,
            'status' => DonationStatus::Pending,
            'amount' => 1000,
        ]);

        $this->service->processPaidDonation($donation);

        $bonus->refresh();
        $this->assertEquals(3, $bonus->quantity_claimed);
    }

    public function test_process_paid_donation_starts_work_when_goal_reached(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::Announced,
            'budget_collected' => 4000,
            'budget_goal' => 5000,
        ]);
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'status' => DonationStatus::Pending,
            'amount' => 1000,
        ]);

        $this->service->processPaidDonation($donation);

        $project->refresh();
        $this->assertEquals(ProjectStatus::InProgress->value, $project->status->value);
        $this->assertDatabaseHas(Notification::class, [
            'user_id' => $project->user_id,
            'type' => 'project_funding_complete',
        ]);
    }

    public function test_process_paid_donation_does_not_start_work_when_goal_not_reached(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::Announced,
            'budget_collected' => 1000,
            'budget_goal' => 5000,
        ]);
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'status' => DonationStatus::Pending,
            'amount' => 500,
        ]);

        $this->service->processPaidDonation($donation);

        $project->refresh();
        $this->assertEquals(ProjectStatus::Announced->value, $project->status->value);
    }

    public function test_process_failed_donation_updates_status(): void
    {
        $donation = Donation::factory()->create([
            'status' => DonationStatus::Pending,
        ]);

        $this->service->processFailedDonation($donation);

        $donation->refresh();
        $this->assertEquals('failed', $donation->status->value ?? $donation->status);
    }

    public function test_process_refund_updates_status_and_decrements_budget(): void
    {
        $project = Project::factory()->create([
            'budget_collected' => 5000,
            'donors_count' => 2,
        ]);
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'status' => 'paid',
            'amount' => 1000,
            'paid_at' => now(),
        ]);

        $this->service->processRefund($donation);

        $donation->refresh();
        $project->refresh();

        $this->assertEquals('refunded', $donation->status->value ?? $donation->status);
        $this->assertEquals(4000, $project->budget_collected);
    }
}
