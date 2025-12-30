<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Message;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Отримати всі мої повідомлення (чат з адміністрацією)
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
     */
    public function contactProjectAuthor(SendMessageRequest $request): JsonResponse
    {
        $projectId = $request->input('project_id');

        if (! $projectId) {
            return response()->json([
                'message' => 'Необхідно вказати project_id',
            ], 422);
        }

        $message = Message::create([
            'user_id' => $request->user()->id,
            'project_id' => $projectId,
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
