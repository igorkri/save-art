<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private ?string $publicKey;

    private ?string $privateKey;

    private string $apiUrl = 'https://www.liqpay.ua/api/3/checkout';

    public function __construct(
        private DonationService $donationService,
    ) {
        $this->publicKey = config('services.liqpay.public_key');
        $this->privateKey = config('services.liqpay.private_key');
    }

    /**
     * Check if LiqPay keys are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->publicKey) && ! empty($this->privateKey);
    }

    /**
     * Ensure LiqPay keys are configured.
     *
     * @throws \RuntimeException
     */
    protected function ensureKeysConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('LiqPay ключі не налаштовані. Встановіть LIQPAY_PUBLIC_KEY та LIQPAY_PRIVATE_KEY в .env файлі.');
        }
    }

    /**
     * Create a payment for donation.
     *
     * @return array{checkout_url: string, payment_id: string, data: string, signature: string}
     *
     * @throws \RuntimeException якщо LiqPay ключі не налаштовані
     */
    public function createPayment(Donation $donation, string $callbackUrl, string $resultUrl): array
    {
        $this->ensureKeysConfigured();

        $orderId = $this->generateOrderId($donation);

        $params = [
            'public_key' => $this->publicKey,
            'version' => '3',
            'action' => 'pay',
            'amount' => (string) $donation->amount,
            'currency' => $donation->currency instanceof \App\Enums\Currency
                ? $donation->currency->value
                : (string) $donation->currency,
            'description' => $this->getDescription($donation),
            'order_id' => $orderId,
            'server_url' => $callbackUrl,
            'result_url' => $resultUrl,
        ];

        $data = base64_encode(json_encode($params));
        $signature = $this->generateSignature($data);

        // Update donation with payment ID
        $donation->update([
            'payment_id' => $orderId,
        ]);

        return [
            'checkout_url' => $this->apiUrl,
            'payment_id' => $orderId,
            'data' => $data,
            'signature' => $signature,
        ];
    }

    /**
     * Process webhook callback from LiqPay.
     */
    public function processWebhook(string $data, string $signature): bool
    {
        // Verify signature
        if (! $this->verifySignature($data, $signature)) {
            Log::warning('PaymentService: Invalid webhook signature');

            return false;
        }

        $payload = json_decode(base64_decode($data), true);

        if (! $payload || ! isset($payload['order_id'], $payload['status'])) {
            Log::warning('PaymentService: Invalid webhook payload', ['data' => $data]);

            return false;
        }

        $donation = Donation::where('payment_id', $payload['order_id'])->first();

        if (! $donation) {
            Log::warning('PaymentService: Donation not found', ['order_id' => $payload['order_id']]);

            return false;
        }

        return $this->handlePaymentStatus($donation, $payload);
    }

    /**
     * Handle payment status from LiqPay.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handlePaymentStatus(Donation $donation, array $payload): bool
    {
        $status = $payload['status'];

        return match ($status) {
            'success', 'sandbox' => $this->handleSuccessPayment($donation, $payload),
            'failure', 'error' => $this->handleFailedPayment($donation, $payload),
            'reversed' => $this->handleRefundedPayment($donation, $payload),
            'hold_wait' => $this->handlePendingPayment($donation),
            default => $this->handleUnknownStatus($donation, $status),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleSuccessPayment(Donation $donation, array $payload): bool
    {
        Log::info('PaymentService: Payment success', [
            'donation_id' => $donation->id,
            'liqpay_id' => $payload['payment_id'] ?? null,
        ]);

        $this->donationService->processPaidDonation($donation);

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleFailedPayment(Donation $donation, array $payload): bool
    {
        Log::info('PaymentService: Payment failed', [
            'donation_id' => $donation->id,
            'err_code' => $payload['err_code'] ?? null,
            'err_description' => $payload['err_description'] ?? null,
        ]);

        $this->donationService->processFailedDonation($donation);

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleRefundedPayment(Donation $donation, array $payload): bool
    {
        Log::info('PaymentService: Payment refunded', [
            'donation_id' => $donation->id,
        ]);

        $this->donationService->processRefund($donation);

        return true;
    }

    protected function handlePendingPayment(Donation $donation): bool
    {
        Log::info('PaymentService: Payment pending (hold)', [
            'donation_id' => $donation->id,
        ]);

        $donation->update(['status' => DonationStatus::Pending]);

        return true;
    }

    protected function handleUnknownStatus(Donation $donation, string $status): bool
    {
        Log::warning('PaymentService: Unknown payment status', [
            'donation_id' => $donation->id,
            'status' => $status,
        ]);

        return false;
    }

    /**
     * Verify LiqPay signature.
     */
    public function verifySignature(string $data, string $signature): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $expectedSignature = $this->generateSignature($data);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate LiqPay signature.
     */
    protected function generateSignature(string $data): string
    {
        return base64_encode(sha1($this->privateKey.$data.$this->privateKey, true));
    }

    /**
     * Generate unique order ID.
     */
    protected function generateOrderId(Donation $donation): string
    {
        return sprintf('donation_%d_%s', $donation->id, now()->format('YmdHis'));
    }

    /**
     * Get payment description.
     */
    protected function getDescription(Donation $donation): string
    {
        $project = $donation->project;

        if (! $project) {
            return 'Донат на підтримку save-art.in.ua';
        }

        return sprintf(
            'Донат для проєкту "%s" на save-art.in.ua',
            $project->title ?: 'Проєкт'
        );
    }
}
