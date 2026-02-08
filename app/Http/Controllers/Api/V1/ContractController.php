<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SignService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SignContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contract API Controller.
 * Implements screens 03.7.5 and 03.7.5.1 from Figma specification.
 *
 * @OA\Tag(
 *     name="Contracts",
 *     description="API для роботи з контрактами митців (03.7.5 Profile Authorized - New - Contract)"
 * )
 */
class ContractController extends Controller
{
    public function __construct(
        protected ContractService $contractService
    ) {}

    /**
     * Get contract template information.
     *
     * GET /api/v1/contracts/template
     *
     * @OA\Get(
     *     path="/v1/contracts/template",
     *     operationId="getContractTemplate",
     *     tags={"Contracts"},
     *     summary="Отримати шаблон контракту",
     *     description="Повертає інформацію про поточний шаблон контракту та доступні сервіси підписання. Екран 03.7.5 Profile Authorized - New - Contract.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Інформація про шаблон контракту",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="version", type="string", example="1.0", description="Версія шаблону контракту"),
     *                 @OA\Property(property="file_url", type="string", nullable=true, example="https://example.com/storage/templates/contract_template.pdf", description="URL файлу шаблону для попереднього перегляду"),
     *                 @OA\Property(property="expires_days", type="integer", example=30, description="Кількість днів до закінчення терміну дії контракту")
     *             ),
     *             @OA\Property(property="sign_services", type="array", description="Доступні сервіси електронного підпису",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="value", type="string", example="diia", description="Код сервісу"),
     *                     @OA\Property(property="label", type="string", example="Дія.Підпис", description="Назва сервісу"),
     *                     @OA\Property(property="enabled", type="boolean", example=true, description="Чи доступний сервіс")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ")
     * )
     */
    public function template(): JsonResponse
    {
        $templateInfo = $this->contractService->getTemplateInfo();

        return response()->json([
            'data' => $templateInfo,
            'sign_services' => $this->getAvailableSignServices(),
        ]);
    }

    /**
     * Download contract template file.
     *
     * GET /api/v1/contracts/template/download
     *
     * @OA\Get(
     *     path="/v1/contracts/template/download",
     *     operationId="downloadContractTemplate",
     *     tags={"Contracts"},
     *     summary="Завантажити шаблон контракту",
     *     description="Завантажує PDF файл шаблону контракту для попереднього перегляду",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="PDF файл шаблону",
     *
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ"),
     *     @OA\Response(response=404, description="Шаблон не знайдено")
     * )
     */
    public function downloadTemplate(): StreamedResponse|JsonResponse
    {
        $templatePath = $this->contractService->getTemplatePath();

        if (! Storage::disk('public')->exists($templatePath)) {
            return response()->json([
                'message' => __('contracts.errors.template_not_found'),
            ], 404);
        }

        return Storage::disk('public')->download($templatePath, 'contract_template.pdf');
    }

    /**
     * Get list of user's contracts.
     *
     * GET /api/v1/contracts
     *
     * @OA\Get(
     *     path="/v1/contracts",
     *     operationId="getContracts",
     *     tags={"Contracts"},
     *     summary="Список контрактів користувача",
     *     description="Повертає список всіх контрактів авторизованого користувача",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список контрактів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Contract")
     *             ),
     *
     *             @OA\Property(property="has_signed_contract", type="boolean", example=true, description="Чи є підписаний контракт останньої версії")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $contracts = $request->user()
            ->contracts()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => ContractResource::collection($contracts),
            'has_signed_contract' => $this->contractService->hasSignedLatestVersion($request->user()),
        ]);
    }

    /**
     * Create a new contract for signing.
     *
     * POST /api/v1/contracts
     *
     * @OA\Post(
     *     path="/v1/contracts",
     *     operationId="createContract",
     *     tags={"Contracts"},
     *     summary="Створити новий контракт",
     *     description="Створює новий контракт для підписання. Якщо вже існує pending контракт поточної версії - повертає його. Екран 03.7.5 Profile Authorized - New - Contract.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=201,
     *         description="Контракт успішно створено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Contract"),
     *             @OA\Property(property="message", type="string", example="Контракт успішно створено")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Повернено існуючий pending контракт",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Contract"),
     *             @OA\Property(property="message", type="string", example="У вас вже є контракт на підписання")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user already has a pending contract
        $existingPending = $user->contracts()
            ->pending()
            ->where('template_version', $this->contractService->getCurrentTemplateVersion())
            ->first();

        if ($existingPending) {
            return response()->json([
                'data' => new ContractResource($existingPending),
                'message' => __('contracts.messages.pending_exists'),
            ], 200);
        }

        $contract = $this->contractService->createContract($user);

        return response()->json([
            'data' => new ContractResource($contract),
            'message' => __('contracts.messages.created'),
        ], 201);
    }

    /**
     * Get a specific contract.
     *
     * GET /api/v1/contracts/{contract}
     *
     * @OA\Get(
     *     path="/v1/contracts/{contract}",
     *     operationId="getContract",
     *     tags={"Contracts"},
     *     summary="Отримати контракт",
     *     description="Повертає інформацію про конкретний контракт користувача",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="contract", in="path", required=true, description="ID контракту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Інформація про контракт",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Contract")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ"),
     *     @OA\Response(response=403, description="Доступ заборонено (не власник)"),
     *     @OA\Response(response=404, description="Контракт не знайдено")
     * )
     */
    public function show(Request $request, Contract $contract): JsonResponse
    {
        $this->authorizeContract($request, $contract);

        return response()->json([
            'data' => new ContractResource($contract),
        ]);
    }

    /**
     * Sign a contract.
     *
     * POST /api/v1/contracts/{contract}/sign
     *
     * @OA\Post(
     *     path="/v1/contracts/{contract}/sign",
     *     operationId="signContract",
     *     tags={"Contracts"},
     *     summary="Підписати контракт",
     *     description="Підписує контракт електронним підписом. Екран 03.7.5.1 Profile Authorized - New - Contract - Added.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="contract", in="path", required=true, description="ID контракту", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"sign_service", "signature_base64"},
     *
     *             @OA\Property(property="sign_service", type="string", enum={"diia", "vchasno", "iit", "manual"}, example="diia", description="Сервіс електронного підпису"),
     *             @OA\Property(property="signature_base64", type="string", example="base64EncodedSignature...", description="Підпис у форматі Base64")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Контракт успішно підписано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Contract"),
     *             @OA\Property(property="message", type="string", example="Контракт успішно підписано")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ"),
     *     @OA\Response(response=403, description="Доступ заборонено (не власник)"),
     *     @OA\Response(response=404, description="Контракт не знайдено"),
     *     @OA\Response(response=422, description="Контракт не в статусі pending або прострочений")
     * )
     */
    public function sign(SignContractRequest $request, Contract $contract): JsonResponse
    {
        $this->authorizeContract($request, $contract);

        if (! $contract->isPending()) {
            return response()->json([
                'message' => __('contracts.errors.not_pending'),
            ], 422);
        }

        if ($contract->isExpired()) {
            return response()->json([
                'message' => __('contracts.errors.expired'),
            ], 422);
        }

        try {
            $signService = SignService::from($request->validated('sign_service'));
            $signedContract = $this->contractService->signContract(
                $contract,
                $signService,
                $request->validated('signature_base64')
            );

            return response()->json([
                'data' => new ContractResource($signedContract),
                'message' => __('contracts.messages.signed'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download a contract file.
     *
     * GET /api/v1/contracts/{contract}/download
     *
     * @OA\Get(
     *     path="/v1/contracts/{contract}/download",
     *     operationId="downloadContract",
     *     tags={"Contracts"},
     *     summary="Завантажити контракт",
     *     description="Завантажує PDF файл контракту. Якщо контракт підписано - повертає підписану версію.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="contract", in="path", required=true, description="ID контракту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="PDF файл контракту",
     *
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ"),
     *     @OA\Response(response=403, description="Доступ заборонено (не власник)"),
     *     @OA\Response(response=404, description="Контракт або файл не знайдено")
     * )
     */
    public function download(Request $request, Contract $contract): StreamedResponse|JsonResponse
    {
        $this->authorizeContract($request, $contract);

        $filePath = $contract->isSigned() && $contract->signed_file_path
            ? $contract->signed_file_path
            : $contract->file_path;

        if (! $filePath || ! Storage::exists($filePath)) {
            return response()->json([
                'message' => __('contracts.errors.file_not_found'),
            ], 404);
        }

        $fileName = $contract->isSigned()
            ? 'contract_signed_'.$contract->id.'.pdf'
            : 'contract_'.$contract->id.'.pdf';

        return Storage::download($filePath, $fileName);
    }

    /**
     * Get the current active contract for the user.
     *
     * GET /api/v1/contracts/active
     *
     * @OA\Get(
     *     path="/v1/contracts/active",
     *     operationId="getActiveContract",
     *     tags={"Contracts"},
     *     summary="Отримати активний контракт",
     *     description="Повертає поточний підписаний контракт користувача",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Активний контракт",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Contract")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Активний контракт не знайдено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="У вас немає активного контракту")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ")
     * )
     */
    public function active(Request $request): JsonResponse
    {
        $contract = $this->contractService->getActiveContract($request->user());

        if (! $contract) {
            return response()->json([
                'data' => null,
                'message' => __('contracts.messages.no_active_contract'),
            ], 404);
        }

        return response()->json([
            'data' => new ContractResource($contract),
        ]);
    }

    /**
     * Delete a contract.
     *
     * DELETE /api/v1/contracts/{contract}
     *
     * @OA\Delete(
     *     path="/v1/contracts/{contract}",
     *     operationId="deleteContract",
     *     tags={"Contracts"},
     *     summary="Видалити контракт",
     *     description="Видаляє контракт користувача. Можна видалити тільки контракти в статусі pending або expired.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="contract", in="path", required=true, description="ID контракту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Контракт успішно видалено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Контракт успішно видалено")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Неавторизований доступ"),
     *     @OA\Response(response=403, description="Доступ заборонено (не власник)"),
     *     @OA\Response(response=404, description="Контракт не знайдено"),
     *     @OA\Response(response=422, description="Неможливо видалити підписаний контракт")
     * )
     */
    public function destroy(Request $request, Contract $contract): JsonResponse
    {
        $this->authorizeContract($request, $contract);

        // Не дозволяємо видаляти підписані контракти
        if ($contract->isSigned()) {
            return response()->json([
                'message' => __('contracts.errors.cannot_delete_signed'),
            ], 422);
        }

        // Видаляємо файл контракту, якщо він існує
        if ($contract->file_path && Storage::disk('public')->exists($contract->file_path)) {
            Storage::disk('public')->delete($contract->file_path);
        }

        // Видаляємо підписаний файл, якщо він існує
        if ($contract->signed_file_path && Storage::disk('public')->exists($contract->signed_file_path)) {
            Storage::disk('public')->delete($contract->signed_file_path);
        }

        $contract->delete();

        return response()->json([
            'message' => __('contracts.messages.deleted'),
        ]);
    }

    /**
     * Get available sign services.
     *
     * @return array<string, array{value: string, label: string, enabled: bool}>
     */
    protected function getAvailableSignServices(): array
    {
        $services = [];
        $config = config('contracts.sign_services', []);

        foreach (SignService::cases() as $service) {
            $serviceConfig = $config[$service->value] ?? [];
            $services[] = [
                'value' => $service->value,
                'label' => $service->label(),
                'enabled' => $serviceConfig['enabled'] ?? false,
            ];
        }

        return $services;
    }

    /**
     * Authorize that the user owns the contract.
     */
    protected function authorizeContract(Request $request, Contract $contract): void
    {
        if ($contract->user_id !== $request->user()->id) {
            abort(403, __('contracts.errors.not_owner'));
        }
    }
}
