<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Message;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

class MessageController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Отримати всі мої повідомлення (чат з адміністрацією)
     *
     * @OA\Get(
     *     path="/v1/messages",
     *     operationId="getMessages",
     *     tags={"Messages"},
     *     summary="Список повідомлень",
     *     description="Повертає всі повідомлення користувача з адміністрацією",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список повідомлень",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $messages = Message::where('user_id', $request->user()->id)
            ->with(['admin', 'project'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return MessageResource::collection($messages);
    }

    /**
     * Відправити повідомлення адміністрації
     *
     * @OA\Post(
     *     path="/v1/messages",
     *     operationId="sendMessage",
     *     tags={"Messages"},
     *     summary="Надіслати повідомлення",
     *     description="Надсилає повідомлення адміністрації платформи",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="content", type="string", description="Текст повідомлення"),
     *             @OA\Property(property="subject", type="string", description="Тема повідомлення"),
     *             @OA\Property(property="project_id", type="integer", description="ID проекту (опціонально)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Повідомлення надіслано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(SendMessageRequest $request): JsonResponse
    {
        $message = Message::create([
            'user_id' => $request->user()->id,
            'project_id' => $request->input('project_id'),
            'content' => $request->input('content'),
            'subject' => $request->input('subject'),
            'direction' => 'user_to_admin',
        ]);

        return response()->json([
            'message' => 'Повідомлення надіслано.',
            'data' => new MessageResource($message),
        ], 201);
    }

    /**
     * Отримати одне повідомлення
     *
     * @OA\Get(
     *     path="/v1/messages/{message}",
     *     operationId="getMessage",
     *     tags={"Messages"},
     *     summary="Отримати повідомлення",
     *     description="Повертає деталі конкретного повідомлення",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="message", in="path", required=true, description="ID повідомлення", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані повідомлення",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Request $request, Message $message): MessageResource|JsonResponse
    {
        // Перевірка доступу
        if ($message->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Позначити як прочитане якщо це повідомлення від адміна
        if ($message->isFromAdmin() && ! $message->isRead()) {
            $message->markAsRead();
        }

        return new MessageResource($message->load(['admin', 'project']));
    }

    /**
     * Позначити всі повідомлення як прочитані
     *
     * @OA\Post(
     *     path="/v1/messages/read-all",
     *     operationId="markAllMessagesAsRead",
     *     tags={"Messages"},
     *     summary="Позначити всі як прочитані",
     *     description="Позначає всі вхідні повідомлення від адміністрації як прочитані",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішно",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="count", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = Message::where('user_id', $request->user()->id)
            ->where('direction', 'admin_to_user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => "Позначено як прочитані: {$count} повідомлень.",
            'count' => $count,
        ]);
    }

    /**
     * Отримати кількість непрочитаних повідомлень
     *
     * @OA\Get(
     *     path="/v1/messages/unread-count",
     *     operationId="getUnreadMessagesCount",
     *     tags={"Messages"},
     *     summary="Кількість непрочитаних",
     *     description="Повертає кількість непрочитаних повідомлень від адміністрації",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Кількість непрочитаних",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="unread_count", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Message::where('user_id', $request->user()->id)
            ->where('direction', 'admin_to_user')
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Надіслати повідомлення автору проєкту (через адміністрацію)
     * Це створює запит до адміністрації, який вони передадуть автору
     *
     * @OA\Post(
     *     path="/v1/projects/{project}/contact-author",
     *     operationId="contactProjectAuthor",
     *     tags={"Messages"},
     *     summary="Зв'язатися з автором проекту",
     *     description="Надсилає повідомлення автору проекту через адміністрацію",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="ID проекту", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="content", type="string", description="Текст повідомлення"),
     *             @OA\Property(property="subject", type="string", description="Тема повідомлення")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Повідомлення надіслано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function contactProjectAuthor(SendMessageRequest $request, Project $project): JsonResponse
    {
        $message = Message::create([
            'user_id' => $request->user()->id,
            'project_id' => $project->id,
            'content' => $request->input('content'),
            'subject' => $request->input('subject', 'Питання щодо проєкту'),
            'direction' => 'user_to_admin',
        ]);

        return response()->json([
            'message' => 'Ваше повідомлення надіслано. Адміністрація передасть його автору проєкту.',
            'data' => new MessageResource($message),
        ], 201);
    }
}
