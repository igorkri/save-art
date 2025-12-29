<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateDonationRequest;
use App\Http\Resources\Api\V1\DonationResource;
use App\Models\Donation;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Services\DonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class DonationController extends Controller
{
    public function __construct(
        private DonationService $donationService
    ) {}

    /**
     * Отримати мої донати (авторизовані)
     */
    public function myDonations(Request $request): AnonymousResourceCollection
    {
        $donations = Donation::query()
            ->with(['project', 'bonus'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return DonationResource::collection($donations);
    }

    /**
     * Ініціювати донат на проєкт
     */
    public function store(CreateDonationRequest $request, Project $project): JsonResponse
    {
        // Перевіряємо, чи проєкт може приймати донати
        if (! $project->canReceiveDonations()) {
            return response()->json([
                'message' => 'Цей проєкт не приймає донати.',
            ], 422);
        }

        $data = $request->validated();

        // Перевіряємо бонус
        $bonus = null;
        if (! empty($data['bonus_id'])) {
            $bonus = ProjectBonus::where('id', $data['bonus_id'])
                ->where('project_id', $project->id)
                ->first();

            if (! $bonus) {
                return response()->json([
                    'message' => 'Бонус не знайдено.',
                ], 422);
            }

            if (! $bonus->isAvailable()) {
                return response()->json([
                    'message' => 'Цей бонус вже недоступний.',
                ], 422);
            }

            if ($data['amount'] < $bonus->min_donation) {
                return response()->json([
                    'message' => "Мінімальна сума для цього бонусу — {$bonus->min_donation}",
                ], 422);
            }
        }

        // Створюємо донат
        $donation = Donation::create([
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
            'bonus_id' => $bonus?->id,

            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'pending',

            'is_anonymous' => $data['is_anonymous'] ?? false,
            'donor_type' => $data['donor_type'],
            'donor_name' => $data['donor_name'] ?? $request->user()?->name,
            'donor_email' => $data['donor_email'] ?? $request->user()?->email,
            'donor_phone' => $data['donor_phone'] ?? null,
            'donor_company_name' => $data['donor_company_name'] ?? null,
            'donor_edrpou' => $data['donor_edrpou'] ?? null,

            'message' => $data['message'] ?? null,
        ]);

        // TODO: Інтеграція з платіжною системою (LiqPay/Fondy/Stripe)
        // Тут повинен бути виклик до платіжного провайдера для ініціалізації платежу
        // $paymentUrl = PaymentService::initiate($donation);

        return response()->json([
            'message' => 'Донат створено. Очікуємо на оплату.',
            'data' => new DonationResource($donation->load(['project', 'bonus'])),
            // 'payment_url' => $paymentUrl,
        ], 201);
    }

    /**
     * Webhook від платіжної системи (LiqPay/Fondy/Stripe)
     */
    public function webhook(Request $request): JsonResponse
    {
        // Логуємо вхідний запит
        Log::info('Payment webhook received', [
            'data' => $request->all(),
        ]);

        // Отримуємо дані з запиту
        // Формат залежить від платіжної системи
        $paymentId = $request->input('payment_id') ?? $request->input('order_id');
        $status = $request->input('status');
        $signature = $request->input('signature');

        // TODO: Валідація підпису від платіжної системи
        // if (! $this->validateSignature($request)) {
        //     return response()->json(['error' => 'Invalid signature'], 400);
        // }

        if (! $paymentId) {
            return response()->json(['error' => 'Missing payment_id'], 400);
        }

        // Знаходимо донат
        $donation = Donation::where('payment_id', $paymentId)->first();

        if (! $donation) {
            Log::warning('Donation not found for payment', ['payment_id' => $paymentId]);

            return response()->json(['error' => 'Donation not found'], 404);
        }

        // Обробляємо статус
        match ($status) {
            'success', 'paid', 'approved' => $this->donationService->processPaidDonation($donation),
            'failure', 'failed', 'declined' => $this->donationService->processFailedDonation($donation),
            'reversed', 'refunded' => $this->donationService->processRefund($donation),
            default => Log::info('Unknown payment status', ['status' => $status, 'donation_id' => $donation->id]),
        };

        return response()->json(['status' => 'ok']);
    }

    /**
     * Показати деталі донату
     */
    public function show(Request $request, Donation $donation): DonationResource|JsonResponse
    {
        // Тільки власник донату або адмін може бачити деталі
        if ($donation->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new DonationResource($donation->load(['project', 'bonus']));
    }
}
