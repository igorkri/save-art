<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
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
     *     description="Повертає список сповіщень авторизованого користувача з пагінацією",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінці", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="unread_only", in="query", description="Тільки непрочитані", @OA\Schema(type="boolean", default=false)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список сповіщень",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Notification")),
     *             @OA\Property(property="unread_count", type="integer", example=5, description="Кількість непрочитаних"),
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
        $query = Notification::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $perPage = min($request->integer('per_page', 15), 50);
        $notifications = $query->paginate($perPage);

        $unreadCount = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => NotificationResource::collection($notifications->items()),
            'unread_count' => $unreadCount,
            'links' => [
                'first' => $notifications->url(1),
                'last' => $notifications->url($notifications->lastPage()),
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'from' => $notifications->firstItem(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'to' => $notifications->lastItem(),
                'total' => $notifications->total(),
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
     *     description="Повертає кількість непрочитаних сповіщень для відображення в badge",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Кількість непрочитаних",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="unread_count", type="integer", example=5)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Отримати конкретне сповіщення
     *
     * @OA\Get(
     *     path="/v1/my/notifications/{id}",
     *     operationId="getNotification",
     *     tags={"Notifications"},
     *     summary="Переглянути сповіщення",
     *     description="Повертає деталі сповіщення та позначає його як прочитане",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID сповіщення", @OA\Schema(type="integer")),
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
    public function show(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notification) {
            return response()->json([
                'message' => 'Сповіщення не знайдено.',
            ], 404);
        }

        // Автоматично позначаємо як прочитане
        $notification->markAsRead();

        return response()->json([
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Позначити сповіщення як прочитане
     *
     * @OA\Post(
     *     path="/v1/my/notifications/{id}/read",
     *     operationId="markNotificationAsRead",
     *     tags={"Notifications"},
     *     summary="Позначити як прочитане",
     *     description="Позначає конкретне сповіщення як прочитане",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID сповіщення", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Сповіщення позначено як прочитане",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Сповіщення позначено як прочитане."),
     *             @OA\Property(property="data", ref="#/components/schemas/Notification")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=404, description="Сповіщення не знайдено")
     * )
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notification) {
            return response()->json([
                'message' => 'Сповіщення не знайдено.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Сповіщення позначено як прочитане.',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Позначити всі сповіщення як прочитані
     *
     * @OA\Post(
     *     path="/v1/my/notifications/read-all",
     *     operationId="markAllNotificationsAsRead",
     *     tags={"Notifications"},
     *     summary="Позначити всі як прочитані",
     *     description="Позначає всі сповіщення користувача як прочитані",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Всі сповіщення позначено як прочитані",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Всі сповіщення позначено як прочитані."),
     *             @OA\Property(property="updated_count", type="integer", example=10)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updatedCount = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Всі сповіщення позначено як прочитані.',
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Видалити сповіщення
     *
     * @OA\Delete(
     *     path="/v1/my/notifications/{id}",
     *     operationId="deleteNotification",
     *     tags={"Notifications"},
     *     summary="Видалити сповіщення",
     *     description="Видаляє конкретне сповіщення",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID сповіщення", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Сповіщення видалено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Сповіщення видалено.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=404, description="Сповіщення не знайдено")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notification) {
            return response()->json([
                'message' => 'Сповіщення не знайдено.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Сповіщення видалено.',
        ]);
    }
}
