<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateDonationRequest;
use App\Http\Resources\Api\V1\DonationResource;
use App\Models\Donation;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Services\DonationService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Donations",
 *     description="API для роботи з донатами"
 * )
 */
class DonationController extends Controller
{
    public function __construct(
        private DonationService $donationService,
        private PaymentService $paymentService,
    ) {}

    /**
     * Отримати мої донати (авторизовані)
     *
     * @OA\Get(
     *     path="/v1/my/donations",
     *     operationId="getMyDonations",
     *     tags={"Donations"},
     *     summary="Мої донати",
     *     description="Повертає список донатів авторизованого користувача",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список донатів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Donation")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function myDonations(Request $request): AnonymousResourceCollection
    {
        $donations = Donation::query()
            ->with(['project.user', 'bonus'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return DonationResource::collection($donations);
    }

    /**
     * Ініціювати донат на проєкт
     *
     * @OA\Post(
     *     path="/v1/projects/{project}/donate",
     *     operationId="createDonation",
     *     tags={"Donations"},
     *     summary="Зробити донат",
     *     description="Ініціює донат на проєкт. Повертає дані для переходу на платіжну систему.",
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         description="ID проєкту",
     *
     *         @OA\Schema(type="integer"),
     *         example=1
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"amount", "currency", "donor_type"},
     *
     *             @OA\Property(property="amount", type="number", format="float", minimum=10, example=500.00, description="Сума донату"),
     *             @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
     *             @OA\Property(property="donor_type", type="string", enum={"personal", "legal"}, example="personal", description="Тип донатера"),
     *             @OA\Property(property="donor_name", type="string", example="Іван Петренко", description="Ім'я донатера"),
     *             @OA\Property(property="donor_email", type="string", format="email", example="ivan@example.com"),
     *             @OA\Property(property="donor_phone", type="string", example="+380501234567"),
     *             @OA\Property(property="donor_company_name", type="string", example="ТОВ 'Компанія'", description="Для юр. осіб"),
     *             @OA\Property(property="donor_edrpou", type="string", example="12345678", description="ЄДРПОУ для юр. осіб"),
     *             @OA\Property(property="is_anonymous", type="boolean", example=false, description="Анонімний донат"),
     *             @OA\Property(property="bonus_id", type="integer", nullable=true, example=1, description="ID бонусу від автора"),
     *             @OA\Property(property="message", type="string", nullable=true, example="Успіхів у творчості!", description="Повідомлення автору")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Донат створено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Донат створено. Очікуємо на оплату."),
     *             @OA\Property(property="data", ref="#/components/schemas/Donation"),
     *             @OA\Property(property="payment", type="object", nullable=true,
     *                 @OA\Property(property="payment_url", type="string", example="https://www.liqpay.ua/checkout/..."),
     *                 @OA\Property(property="payment_id", type="string", example="abc123")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Помилка валідації або проєкт не приймає донати",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *
     *     @OA\Response(response=404, description="Проєкт не знайдено")
     * )
     */
    public function store(CreateDonationRequest $request, Project $project): JsonResponse
    {
        // Перевіряємо, чи проєкт може приймати донати
        if (! $project->canReceiveDonations()) {
            Log::channel('donations')->warning('Донат проєкту: проєкт не приймає донати', [
                'project' => $project->slug,
                'status' => $project->status,
            ]);

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
                Log::channel('donations')->warning('Донат проєкту: бонус не знайдено', [
                    'project' => $project->slug,
                    'bonus_id' => $data['bonus_id'],
                ]);

                return response()->json([
                    'message' => 'Бонус не знайдено.',
                ], 422);
            }

            if (! $bonus->isAvailable()) {
                Log::channel('donations')->warning('Донат проєкту: бонус недоступний', [
                    'project' => $project->slug,
                    'bonus_id' => $bonus->id,
                ]);

                return response()->json([
                    'message' => 'Цей бонус вже недоступний.',
                ], 422);
            }

            if ($data['amount'] < $bonus->min_donation) {
                Log::channel('donations')->warning('Донат проєкту: сума менша за мінімум бонусу', [
                    'project' => $project->slug,
                    'bonus_id' => $bonus->id,
                    'amount' => $data['amount'],
                    'min_donation' => $bonus->min_donation,
                ]);

                return response()->json([
                    'message' => "Мінімальна сума для цього бонусу — {$bonus->min_donation}",
                ], 422);
            }
        }

        // Створюємо донат
        $donor = $request->user('sanctum');
        $donation = Donation::create([
            'project_id' => $project->id,
            'user_id' => $donor?->id,
            'project_bonus_id' => $bonus?->id,
            'donation_type' => Donation::TYPE_PROJECT,

            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'pending',

            'is_anonymous' => $data['is_anonymous'] ?? false,
            'donor_type' => $data['donor_type'],
            'donor_name' => $data['donor_name'] ?? $donor?->name,
            'donor_email' => $data['donor_email'] ?? $donor?->email,
        ]);

        $this->donationService->registerPendingAsCollected($donation);

        // Ініціалізуємо платіж через PaymentService
        $paymentData = null;
        $paymentConfigured = $this->paymentService->isConfigured();
        if ($paymentConfigured) {
            $callbackUrl = route('api.v1.payments.webhook');
            $resultUrl = config('app.frontend_url', config('app.url')).'/payment/result';

            $paymentData = $this->paymentService->createPayment($donation, $callbackUrl, $resultUrl);
        }

        Log::channel('donations')->info('Донат проєкту створено', [
            'donation_id' => $donation->id,
            'project' => $project->slug,
            'user_id' => $donor?->id,
            'is_guest' => ! $donor,
            'donor_type' => $donation->donor_type,
            'is_anonymous' => $donation->is_anonymous,
            'amount' => $donation->amount,
            'currency' => $donation->currency,
            'bonus_id' => $bonus?->id,
            'payment_configured' => $paymentConfigured,
            'payment_initialized' => (bool) $paymentData,
        ]);

        return response()->json([
            'message' => 'Донат створено. Очікуємо на оплату.',
            'data' => new DonationResource($donation->load(['project', 'bonus'])),
            'payment' => $paymentData,
        ], 201);
    }

    /**
     * Ініціювати донат на платформу
     *
     * @OA\Post(
     *     path="/v1/donations/platform",
     *     operationId="createPlatformDonation",
     *     tags={"Donations"},
     *     summary="Зробити донат на платформу",
     *     description="Ініціює донат на платформу Save-Art (не прив'язаний до конкретного проекту). Повертає дані для переходу на платіжну систему.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"amount", "currency", "donor_type"},
     *
     *             @OA\Property(property="amount", type="number", format="float", minimum=10, example=500.00, description="Сума донату"),
     *             @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
     *             @OA\Property(property="donor_type", type="string", enum={"personal", "legal"}, example="personal", description="Тип донатера"),
     *             @OA\Property(property="donor_name", type="string", example="Іван Петренко", description="Ім'я донатера"),
     *             @OA\Property(property="donor_email", type="string", format="email", example="ivan@example.com"),
     *             @OA\Property(property="donor_phone", type="string", example="+380501234567"),
     *             @OA\Property(property="donor_company_name", type="string", example="ТОВ 'Компанія'", description="Для юр. осіб"),
     *             @OA\Property(property="donor_edrpou", type="string", example="12345678", description="ЄДРПОУ для юр. осіб"),
     *             @OA\Property(property="is_anonymous", type="boolean", example=false, description="Анонімний донат"),
     *             @OA\Property(property="message", type="string", nullable=true, example="Дякую за вашу роботу!", description="Повідомлення платформі")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Донат створено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Донат на платформу створено. Очікуємо на оплату."),
     *             @OA\Property(property="data", ref="#/components/schemas/Donation"),
     *             @OA\Property(property="payment", type="object", nullable=true,
     *                 @OA\Property(property="payment_url", type="string", example="https://www.liqpay.ua/checkout/..."),
     *                 @OA\Property(property="payment_id", type="string", example="abc123")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function storePlatformDonation(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'amount' => ['required', 'numeric', 'min:1'],
                'currency' => ['required', 'string', 'in:UAH,USD,EUR'],
                'donor_type' => ['required', 'string', 'in:personal,legal'],
                'donor_name' => ['nullable', 'string', 'max:255'],
                'donor_email' => ['nullable', 'email', 'max:255'],
                'donor_phone' => ['nullable', 'string', 'max:20'],
                'donor_company_name' => ['nullable', 'string', 'max:255'],
                'donor_edrpou' => ['nullable', 'string', 'max:10'],
                'is_anonymous' => ['nullable', 'boolean'],
                'message' => ['nullable', 'string', 'max:1000'],
            ]);
        } catch (ValidationException $e) {
            Log::channel('donations')->warning('Донат платформі: валідацію не пройдено', [
                'user_id' => $request->user('sanctum')?->id,
                'is_guest' => ! $request->user('sanctum'),
                'input' => $request->except(['donor_edrpou', 'donor_company_name']),
                'errors' => $e->errors(),
            ]);

            throw $e;
        }

        Log::channel('donations')->info('Донат платформі: валідацію пройдено', [
            'user_id' => $request->user('sanctum')?->id,
            'is_guest' => ! $request->user('sanctum'),
            'donor_type' => $data['donor_type'],
            'is_anonymous' => $data['is_anonymous'] ?? false,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
        ]);

        // Створюємо донат на платформу
        $donor = $request->user('sanctum');
        $donation = Donation::create([
            'project_id' => null,
            'user_id' => $donor?->id,
            'donation_type' => Donation::TYPE_PLATFORM,

            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'pending',

            'is_anonymous' => $data['is_anonymous'] ?? false,
            'donor_type' => $data['donor_type'],
            'donor_name' => $data['donor_name'] ?? $donor?->name,
            'donor_email' => $data['donor_email'] ?? $donor?->email,
        ]);

        // Ініціалізуємо платіж через PaymentService
        $paymentData = null;
        $paymentConfigured = $this->paymentService->isConfigured();
        if ($paymentConfigured) {
            $callbackUrl = route('api.v1.payments.webhook');
            $resultUrl = config('app.frontend_url', config('app.url')).'/payment/result';

            $paymentData = $this->paymentService->createPayment($donation, $callbackUrl, $resultUrl);
        }

        Log::channel('donations')->info('Донат платформі створено', [
            'donation_id' => $donation->id,
            'user_id' => $donor?->id,
            'is_guest' => ! $donor,
            'donor_type' => $donation->donor_type,
            'is_anonymous' => $donation->is_anonymous,
            'amount' => $donation->amount,
            'currency' => $donation->currency,
            'payment_configured' => $paymentConfigured,
            'payment_initialized' => (bool) $paymentData,
        ]);

        return response()->json([
            'message' => 'Донат на платформу створено. Очікуємо на оплату.',
            'data' => new DonationResource($donation),
            'payment' => $paymentData,
        ], 201);
    }

    /**
     * Webhook від платіжної системи (LiqPay)
     */
    public function webhook(Request $request): JsonResponse
    {
        Log::info('Payment webhook received', [
            'data' => $request->except(['data', 'signature']),
        ]);

        $data = $request->input('data');
        $signature = $request->input('signature');

        if (! $data || ! $signature) {
            return response()->json(['error' => 'Missing data or signature'], 400);
        }

        $result = $this->paymentService->processWebhook($data, $signature);

        if (! $result) {
            return response()->json(['error' => 'Webhook processing failed'], 400);
        }

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
