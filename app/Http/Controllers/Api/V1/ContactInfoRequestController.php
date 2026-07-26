<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContactInfoRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactInfoRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="ContactInfoRequests",
 *     description="Запити на отримання контактної інформації юридичної особи митця (03.2.4)"
 * )
 */
class ContactInfoRequestController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Запросити контактну інформацію юридичної особи автора проєкту
     *
     * @OA\Post(
     *     path="/v1/projects/{project}/contact-request",
     *     operationId="requestContactInfo",
     *     tags={"ContactInfoRequests"},
     *     summary="Запросити контактну інформацію автора проєкту",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="ID проєкту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=201, description="Запит створено"),
     *     @OA\Response(response=422, description="Некоректний запит (наприклад, запит вже існує)"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        return $this->createRequest($request, $project->user_id, $project->id);
    }

    /**
     * Запросити контактну інформацію юридичної особи з публічного профілю
     * митця, без прив'язки до конкретного проєкту.
     *
     * @OA\Post(
     *     path="/v1/users/{user}/contact-request",
     *     operationId="requestContactInfoFromProfile",
     *     tags={"ContactInfoRequests"},
     *     summary="Запросити контактну інформацію з публічного профілю митця",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, description="ID митця", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=201, description="Запит створено"),
     *     @OA\Response(response=422, description="Некоректний запит (наприклад, запит вже існує)"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function storeForUser(Request $request, User $user): JsonResponse
    {
        return $this->createRequest($request, $user->id, null);
    }

    /**
     * Отримати статус останнього запиту поточного користувача на контакти
     * вказаного митця (для відображення стану кнопки на публічному профілі).
     *
     * @OA\Get(
     *     path="/v1/users/{user}/contact-request",
     *     operationId="getContactInfoRequestStatus",
     *     tags={"ContactInfoRequests"},
     *     summary="Статус запиту контактної інформації митця",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="user", in="path", required=true, description="ID митця", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Статус запиту (null, якщо запитів не було)")
     * )
     */
    public function statusForUser(Request $request, User $user): JsonResponse
    {
        $status = ContactInfoRequest::where('requester_id', $request->user()->id)
            ->where('owner_id', $user->id)
            ->latest()
            ->value('status');

        return response()->json([
            'data' => ['status' => $status],
        ]);
    }

    private function createRequest(Request $request, int $ownerId, ?int $projectId): JsonResponse
    {
        $requesterId = $request->user()->id;

        if ($ownerId === $requesterId) {
            return response()->json(['message' => 'Ви не можете надіслати запит самому собі.'], 422);
        }

        $alreadyPending = ContactInfoRequest::where('requester_id', $requesterId)
            ->where('owner_id', $ownerId)
            ->where('status', ContactInfoRequestStatus::Pending)
            ->exists();

        if ($alreadyPending) {
            return response()->json(['message' => 'Запит вже надіслано та очікує на рішення.'], 422);
        }

        $contactInfoRequest = ContactInfoRequest::create([
            'requester_id' => $requesterId,
            'owner_id' => $ownerId,
            'project_id' => $projectId,
            'status' => ContactInfoRequestStatus::Pending,
        ]);

        $this->notificationService->notifyContactRequested($contactInfoRequest);

        return response()->json([
            'message' => 'Запит на отримання контактної інформації надіслано.',
            'data' => ['id' => $contactInfoRequest->id],
        ], 201);
    }

    /**
     * Надати контактну інформацію за запитом
     *
     * @OA\Post(
     *     path="/v1/my/contact-requests/{contactInfoRequest}/grant",
     *     operationId="grantContactInfoRequest",
     *     tags={"ContactInfoRequests"},
     *     summary="Надати контактну інформацію за запитом",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="contactInfoRequest", in="path", required=true, description="ID запиту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Контактну інформацію надано"),
     *     @OA\Response(response=403, description="Не є власником запиту"),
     *     @OA\Response(response=422, description="Запит вже опрацьовано")
     * )
     */
    public function grant(Request $request, ContactInfoRequest $contactInfoRequest): JsonResponse
    {
        return $this->decide($request, $contactInfoRequest, ContactInfoRequestStatus::Granted);
    }

    /**
     * Відхилити запит на отримання контактної інформації
     *
     * @OA\Post(
     *     path="/v1/my/contact-requests/{contactInfoRequest}/reject",
     *     operationId="rejectContactInfoRequest",
     *     tags={"ContactInfoRequests"},
     *     summary="Відхилити запит на отримання контактної інформації",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="contactInfoRequest", in="path", required=true, description="ID запиту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Запит відхилено"),
     *     @OA\Response(response=403, description="Не є власником запиту"),
     *     @OA\Response(response=422, description="Запит вже опрацьовано")
     * )
     */
    public function reject(Request $request, ContactInfoRequest $contactInfoRequest): JsonResponse
    {
        return $this->decide($request, $contactInfoRequest, ContactInfoRequestStatus::Rejected);
    }

    private function decide(Request $request, ContactInfoRequest $contactInfoRequest, ContactInfoRequestStatus $decision): JsonResponse
    {
        if ($contactInfoRequest->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Ви не можете опрацювати цей запит.'], 403);
        }

        if ($contactInfoRequest->status !== ContactInfoRequestStatus::Pending) {
            return response()->json(['message' => 'Запит вже опрацьовано.'], 422);
        }

        $contactInfoRequest->update([
            'status' => $decision,
            'decided_at' => now(),
        ]);

        if ($decision === ContactInfoRequestStatus::Granted) {
            $this->notificationService->notifyContactGranted($contactInfoRequest);
        } else {
            $this->notificationService->notifyContactRejected($contactInfoRequest);
        }

        return response()->json([
            'message' => $decision === ContactInfoRequestStatus::Granted
                ? 'Контактну інформацію надано.'
                : 'Запит відхилено.',
        ]);
    }
}
