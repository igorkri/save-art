<?php

namespace Tests\Unit\Services;

use App\Enums\Currency;
use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Project;
use App\Services\DonationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set test credentials
        config([
            'services.liqpay.public_key' => 'test_public_key',
            'services.liqpay.private_key' => 'test_private_key',
        ]);

        $this->service = app(PaymentService::class);
    }

    public function test_is_configured_returns_true_when_keys_set(): void
    {
        $this->assertTrue($this->service->isConfigured());
    }

    public function test_is_configured_returns_false_when_keys_missing(): void
    {
        config([
            'services.liqpay.public_key' => '',
            'services.liqpay.private_key' => '',
        ]);

        $service = new PaymentService(app(DonationService::class));

        $this->assertFalse($service->isConfigured());
    }

    public function test_create_payment_returns_checkout_data(): void
    {
        $project = Project::factory()->create([
            'title' => ['uk' => 'Тестовий проєкт', 'en' => 'Test Project'],
        ]);
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'amount' => 1000,
            'currency' => Currency::UAH,
            'status' => DonationStatus::Pending,
        ]);

        $result = $this->service->createPayment(
            $donation,
            'https://example.com/webhook',
            'https://example.com/result'
        );

        $this->assertArrayHasKey('checkout_url', $result);
        $this->assertArrayHasKey('payment_id', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('signature', $result);
        $this->assertStringStartsWith('donation_', $result['payment_id']);
    }

    public function test_create_payment_updates_donation_payment_id(): void
    {
        $project = Project::factory()->create();
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'amount' => 500,
            'currency' => Currency::UAH,
            'status' => DonationStatus::Pending,
            'payment_id' => null,
        ]);

        $result = $this->service->createPayment(
            $donation,
            'https://example.com/webhook',
            'https://example.com/result'
        );

        $donation->refresh();
        $this->assertNotNull($donation->payment_id);
        $this->assertEquals($result['payment_id'], $donation->payment_id);
    }

    public function test_verify_signature_validates_correctly(): void
    {
        // Create a valid signature
        $data = base64_encode(json_encode(['test' => 'data']));
        $privateKey = 'test_private_key';
        $expectedSignature = base64_encode(sha1($privateKey.$data.$privateKey, true));

        $this->assertTrue($this->service->verifySignature($data, $expectedSignature));
    }

    public function test_verify_signature_fails_for_invalid_signature(): void
    {
        $data = base64_encode(json_encode(['test' => 'data']));

        $this->assertFalse($this->service->verifySignature($data, 'invalid_signature'));
    }

    public function test_process_webhook_returns_false_for_invalid_signature(): void
    {
        $data = base64_encode(json_encode(['order_id' => 'test', 'status' => 'success']));

        $result = $this->service->processWebhook($data, 'invalid_signature');

        $this->assertFalse($result);
    }

    public function test_process_webhook_returns_false_for_missing_donation(): void
    {
        $payload = ['order_id' => 'nonexistent_123', 'status' => 'success'];
        $data = base64_encode(json_encode($payload));
        $privateKey = 'test_private_key';
        $signature = base64_encode(sha1($privateKey.$data.$privateKey, true));

        $result = $this->service->processWebhook($data, $signature);

        $this->assertFalse($result);
    }

    public function test_process_webhook_handles_success_status(): void
    {
        $project = Project::factory()->create([
            'budget_collected' => 0,
            'donors_count' => 0,
        ]);
        $donation = Donation::factory()->create([
            'project_id' => $project->id,
            'payment_id' => 'donation_123_test',
            'status' => DonationStatus::Pending,
            'amount' => 1000,
        ]);

        $payload = ['order_id' => 'donation_123_test', 'status' => 'success'];
        $data = base64_encode(json_encode($payload));
        $privateKey = 'test_private_key';
        $signature = base64_encode(sha1($privateKey.$data.$privateKey, true));

        $result = $this->service->processWebhook($data, $signature);

        $this->assertTrue($result);
        $donation->refresh();
        $this->assertEquals('paid', $donation->status->value ?? $donation->status);
    }

    public function test_process_webhook_handles_failure_status(): void
    {
        $donation = Donation::factory()->create([
            'payment_id' => 'donation_456_test',
            'status' => DonationStatus::Pending,
        ]);

        $payload = ['order_id' => 'donation_456_test', 'status' => 'failure'];
        $data = base64_encode(json_encode($payload));
        $privateKey = 'test_private_key';
        $signature = base64_encode(sha1($privateKey.$data.$privateKey, true));

        $result = $this->service->processWebhook($data, $signature);

        $this->assertTrue($result);
        $donation->refresh();
        $this->assertEquals('failed', $donation->status->value ?? $donation->status);
    }
}
