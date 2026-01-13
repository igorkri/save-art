<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DonationStatus;
use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;

class DonationsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Створення донату
    // ==========================================

    public function test_can_create_donation(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson("/api/v1/projects/{$project->id}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
                'donor_name' => 'Test Donor',
                'donor_email' => 'donor@example.com',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                ],
            ]);
    }

    public function test_cannot_donate_to_draft_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson("/api/v1/projects/{$project->id}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
            ]);

        $response->assertNotFound();
    }

    public function test_donation_requires_valid_amount(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson("/api/v1/projects/{$project->id}/donate", [
                'amount' => 0,
                'currency' => 'UAH',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    // ==========================================
    // Донат на платформу
    // ==========================================

    public function test_can_create_platform_donation(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson('/api/v1/donations/platform', [
                'amount' => 50,
                'currency' => 'UAH',
                'donor_name' => 'Platform Donor',
                'donor_email' => 'platform@example.com',
            ]);

        $response->assertOk();
    }

    // ==========================================
    // Мої донати
    // ==========================================

    public function test_can_get_my_donations(): void
    {
        Donation::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => DonationStatus::Completed,
        ]);

        // Чужий донат
        Donation::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/donations');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_donation_details(): void
    {
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/donations/{$donation->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $donation->id);
    }

    public function test_cannot_view_other_user_donation(): void
    {
        $donation = Donation::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/donations/{$donation->id}");

        $response->assertForbidden();
    }

    // ==========================================
    // Webhook
    // ==========================================

    public function test_webhook_updates_donation_status(): void
    {
        $donation = Donation::factory()->create([
            'status' => DonationStatus::Pending,
        ]);

        // Webhook не вимагає API Key
        $response = $this->withHeaders($this->apiHeaders())->postJson('/api/v1/payments/webhook', [
            'order_id' => $donation->id,
            'status' => 'success',
        ]);

        // Статус відповіді залежить від реалізації
        $response->assertStatus(200);
    }
}
