<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="API для роботи зі сповіщеннями (03.2.4)"
 * )
 */
class NotificationController extends Controller
{
    /**
     * Отримати список сповіщень користувача
     *
     * @OA\Get(
     *     path="/v1/my/notifications",
     *     operationId="getMyNotifications",
     *     tags={"Notifications"},
     *     summary="Мої сповіщення (03.2.4)",
     *     description="Повертає об'єднаний список сповіщень (app_notifications) та повідомлень від адміністрації (messages) з пагінацією",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінці", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="unread_only", in="query", description="Тільки непрочитані", @OA\Schema(type="boolean", default=false)),
     *     @OA\Parameter(name="type", in="query", description="Фільтр за типом (notification, message, all)", @OA\Schema(type="string", enum={"notification", "message", "all"}, default="all")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список сповіщень",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Notification")),
     *             @OA\Property(property="unread_count", type="integer", example=5, description="Кількість непрочитаних"),
     *             @OA\Property(property="unread_notifications", type="integer", example=3, description="Непрочитаних сповіщень"),
     *             @OA\Property(property="unread_messages", type="integer", example=2, description="Непрочитаних повідомлень"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = min($request->integer('per_page', 15), 50);
        $page = $request->integer('page', 1);
        $unreadOnly = $request->boolean('unread_only');
        $typeFilter = $request->string('type', 'all')->toString();

        // Збираємо всі елементи
        $items = collect();

        // Додаємо сповіщення з app_notifications
        if ($typeFilter === 'all' || $typeFilter === 'notification') {
            $notificationsQuery = Notification::where('user_id', $userId);
            if ($unreadOnly) {
                $notificationsQuery->whereNull('read_at');
            }
            $notifications = $notificationsQuery->get()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'source' => 'notification',
                    'type' => $notification->type instanceof \App\Enums\NotificationType
                        ? $notification->type->value
                        : $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'is_read' => $notification->is_read,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'updated_at' => $notification->updated_at,
                ];
            });
            $items = $items->merge($notifications);
        }

        // Додаємо повідомлення з messages (тільки від адміна до користувача)
        if ($typeFilter === 'all' || $typeFilter === 'message') {
            $messagesQuery = Message::where('user_id', $userId)
                ->where('direction', 'admin_to_user');
            if ($unreadOnly) {
                $messagesQuery->whereNull('read_at');
            }
            $messages = $messagesQuery->get()->map(function ($message) {
                return [
                    'id' => $message->id,
                    'source' => 'message',
                    'type' => 'admin_message',
                    'title' => [
                        'uk' => $message->subject ?? 'Повідомлення від адміністрації',
                        'en' => $message->subject ?? 'Message from administration',
                    ],
                    'message' => [
                        'uk' => $message->content,
                        'en' => $message->content,
                    ],
                    'data' => [
                        'project_id' => $message->project_id,
                        'admin_id' => $message->admin_id,
                    ],
                    'is_read' => $message->read_at !== null,
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ];
            });
            $items = $items->merge($messages);
        }

        // Сортуємо за датою створення (нові перші). Саме created_at, а не updated_at:
        // read-all масово оновлює updated_at всіх непрочитаних до одного моменту
        // і "зрівнює" їх у сортуванні, ламаючи хронологічний порядок.
        $items = $items->sortByDesc('created_at')->values();

        // Пагінація
        $total = $items->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $items->slice($offset, $perPage)->values();

        // Рахуємо непрочитані
        $unreadNotifications = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $unreadMessages = Message::where('user_id', $userId)
            ->where('direction', 'admin_to_user')
            ->whereNull('read_at')
            ->count();

        $baseUrl = url('/api/v1/my/notifications');

        return response()->json([
            'data' => $paginatedItems,
            'unread_count' => $unreadNotifications + $unreadMessages,
            'unread_notifications' => $unreadNotifications,
            'unread_messages' => $unreadMessages,
            'links' => [
                'first' => $baseUrl.'?page=1',
                'last' => $baseUrl.'?page='.$lastPage,
                'prev' => $page > 1 ? $baseUrl.'?page='.($page - 1) : null,
                'next' => $page < $lastPage ? $baseUrl.'?page='.($page + 1) : null,
            ],
            'meta' => [
                'current_page' => $page,
                'from' => $total > 0 ? $offset + 1 : null,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => $total > 0 ? min($offset + $perPage, $total) : null,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Отримати кількість непрочитаних сповіщень
     *
     * @OA\Get(
     *     path="/v1/my/notifications/unread-count",
     *     operationId="getUnreadNotificationsCount",
     *     tags={"Notifications"},
     *     summary="Кількість непрочитаних сповіщень",
     *     description="Повертає кількість непрочитаних сповіщень та повідомлень для відображення в badge",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Кількість непрочитаних",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="unread_count", type="integer", example=5, description="Загальна кількість непрочитаних"),
     *             @OA\Property(property="unread_notifications", type="integer", example=3, description="Непрочитаних сповіщень"),
     *             @OA\Property(property="unread_messages", type="integer", example=2, description="Непрочитаних повідомлень")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $unreadNotifications = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $unreadMessages = Message::where('user_id', $userId)
            ->where('direction', 'admin_to_user')
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $unreadNotifications + $unreadMessages,
            'unread_notifications' => $unreadNotifications,
            'unread_messages' => $unreadMessages,
        ]);
    }

    /**
     * Отримати конкретне сповіщення
     *
     * @OA\Get(
     *     path="/v1/my/notifications/{source}/{id}",
     *     operationId="getNotification",
     *     tags={"Notifications"},
     *     summary="Переглянути сповіщення",
     *     description="Повертає деталі сповіщення або повідомлення та позначає як прочитане",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="source", in="path", required=true, description="Джерело (notification або message)", @OA\Schema(type="string", enum={"notification", "message"})),
     *     @OA\Parameter(name="id", in="path", required=true, description="ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Деталі сповіщення",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Notification")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=404, description="Сповіщення не знайдено")
     * )
     */
    public function show(Request $request, string $source, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        if ($source === 'message') {
            $message = Message::where('id', $id)
                ->where('user_id', $userId)
                ->where('direction', 'admin_to_user')
                ->first();

            if (! $message) {
                return response()->json(['message' => 'Повідомлення не знайдено.'], 404);
            }

            if (! $message->read_at) {
                $message->update(['read_at' => now()]);
            }

            return response()->json([
                'data' => [
                    'id' => $message->id,
                    'source' => 'message',
                    'type' => 'admin_message',
                    'title' => ['uk' => $message->subject, 'en' => $message->subject],
                    'message' => ['uk' => $message->content, 'en' => $message->content],
                    'data' => ['project_id' => $message->project_id, 'admin_id' => $message->admin_id],
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ],
            ]);
        }

        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $notification) {
            return response()->json(['message' => 'Сповіщення не знайдено.'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Позначити сповіщення як прочитане
     *
     * @OA\Post(
     *     path="/v1/my/notifications/{source}/{id}/read",
     *     operationId="markNotificationAsRead",
     *     tags={"Notifications"},
     *     summary="Позначити як прочитане",
     *     description="Позначає сповіщення або повідомлення як прочитане",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="source", in="path", required=true, description="Джерело (notification або message)", @OA\Schema(type="string", enum={"notification", "message"})),
     *     @OA\Parameter(name="id", in="path", required=true, description="ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Позначено як прочитане",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Сповіщення позначено як прочитане.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=404, description="Не знайдено")
     * )
     */
    public function markAsRead(Request $request, string $source, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        if ($source === 'message') {
            $message = Message::where('id', $id)
                ->where('user_id', $userId)
                ->where('direction', 'admin_to_user')
                ->first();

            if (! $message) {
                return response()->json(['message' => 'Повідомлення не знайдено.'], 404);
            }

            $message->update(['read_at' => now()]);

            return response()->json(['message' => 'Повідомлення позначено як прочитане.']);
        }

        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $notification) {
            return response()->json(['message' => 'Сповіщення не знайдено.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Сповіщення позначено як прочитане.']);
    }

    /**
     * Позначити всі сповіщення як прочитані
     *
     * @OA\Post(
     *     path="/v1/my/notifications/read-all",
     *     operationId="markAllNotificationsAsRead",
     *     tags={"Notifications"},
     *     summary="Позначити всі як прочитані",
     *     description="Позначає всі сповіщення та повідомлення користувача як прочитані",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Всі сповіщення позначено як прочитані",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Всі сповіщення позначено як прочитані."),
     *             @OA\Property(property="updated_notifications", type="integer", example=5),
     *             @OA\Property(property="updated_messages", type="integer", example=3),
     *             @OA\Property(property="updated_total", type="integer", example=8)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $updatedNotifications = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $updatedMessages = Message::where('user_id', $userId)
            ->where('direction', 'admin_to_user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Всі сповіщення позначено як прочитані.',
            'updated_notifications' => $updatedNotifications,
            'updated_messages' => $updatedMessages,
            'updated_total' => $updatedNotifications + $updatedMessages,
        ]);
    }

    /**
     * Видалити сповіщення
     *
     * @OA\Delete(
     *     path="/v1/my/notifications/{source}/{id}",
     *     operationId="deleteNotification",
     *     tags={"Notifications"},
     *     summary="Видалити сповіщення",
     *     description="Видаляє сповіщення або повідомлення",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="source", in="path", required=true, description="Джерело (notification або message)", @OA\Schema(type="string", enum={"notification", "message"})),
     *     @OA\Parameter(name="id", in="path", required=true, description="ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Видалено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Сповіщення видалено.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=404, description="Не знайдено")
     * )
     */
    public function destroy(Request $request, string $source, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        if ($source === 'message') {
            $message = Message::where('id', $id)
                ->where('user_id', $userId)
                ->where('direction', 'admin_to_user')
                ->first();

            if (! $message) {
                return response()->json(['message' => 'Повідомлення не знайдено.'], 404);
            }

            $message->delete();

            return response()->json(['message' => 'Повідомлення видалено.']);
        }

        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $notification) {
            return response()->json(['message' => 'Сповіщення не знайдено.'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Сповіщення видалено.']);
    }
}
