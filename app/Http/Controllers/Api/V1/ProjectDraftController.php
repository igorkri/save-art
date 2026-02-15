<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class ProjectDraftController extends Controller
{
    /**
     * Отримати список чернеток користувача
     *
     * @OA\Get(
     *     path="/v1/my/drafts",
     *     operationId="getDrafts",
     *     tags={"Drafts"},
     *     summary="Список чернеток користувача",
     *     description="Повертає список всіх чернеток поточного користувача",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список чернеток",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="drafts", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="project_id", type="integer", nullable=true),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="data", type="object"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="count", type="integer", example=5)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $drafts = ProjectDraft::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [ProjectDraft::STATUS_DELETED])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (ProjectDraft $draft) => $this->formatDraft($draft));

        return response()->json([
            'result' => true,
            'data' => [
                'drafts' => $drafts,
                'count' => $drafts->count(),
            ],
        ]);
    }

    /**
     * Створити нову чернетку
     *
     * @OA\Post(
     *     path="/v1/my/drafts",
     *     operationId="storeDraft",
     *     tags={"Drafts"},
     *     summary="Створити чернетку",
     *     description="Створює нову чернетку з переданими даними",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object", description="Дані чернетки (довільний JSON)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Чернетку створено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="draft", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        $draft = ProjectDraft::create([
            'user_id' => $user->id,
            'data' => $validated['data'],
            'status' => ProjectDraft::STATUS_NEW,
        ]);

        return response()->json([
            'result' => true,
            'message' => 'Чернетку створено',
            'data' => [
                'draft' => $this->formatDraft($draft),
            ],
        ], 201);
    }

    /**
     * Отримати одну чернетку
     *
     * @OA\Get(
     *     path="/v1/my/drafts/{id}",
     *     operationId="showDraft",
     *     tags={"Drafts"},
     *     summary="Отримати чернетку",
     *     description="Повертає детальну інформацію про чернетку",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID чернетки", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані чернетки",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="draft", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $draft = ProjectDraft::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [ProjectDraft::STATUS_DELETED])
            ->findOrFail($id);

        return response()->json([
            'result' => true,
            'data' => [
                'draft' => $this->formatDraft($draft),
            ],
        ]);
    }

    /**
     * Оновити чернетку
     *
     * @OA\Put(
     *     path="/v1/my/drafts/{id}",
     *     operationId="updateDraft",
     *     tags={"Drafts"},
     *     summary="Оновити чернетку",
     *     description="Оновлює існуючу чернетку",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID чернетки", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object", description="Дані чернетки (довільний JSON)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Чернетку оновлено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="draft", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $draft = ProjectDraft::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [ProjectDraft::STATUS_DELETED, ProjectDraft::STATUS_EXPORTED])
            ->findOrFail($id);

        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        $draft->update([
            'data' => $validated['data'],
        ]);

        return response()->json([
            'result' => true,
            'message' => 'Чернетку оновлено',
            'data' => [
                'draft' => $this->formatDraft($draft->fresh()),
            ],
        ]);
    }

    /**
     * Видалити чернетку (soft delete - змінює статус на deleted)
     *
     * @OA\Delete(
     *     path="/v1/my/drafts/{id}",
     *     operationId="deleteDraft",
     *     tags={"Drafts"},
     *     summary="Видалити чернетку",
     *     description="Видаляє чернетку користувача (soft delete)",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID чернетки", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Чернетку видалено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        $draft = ProjectDraft::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [ProjectDraft::STATUS_DELETED])
            ->findOrFail($id);

        $draft->update([
            'status' => ProjectDraft::STATUS_DELETED,
        ]);

        return response()->json([
            'result' => true,
            'message' => 'Чернетку видалено',
        ]);
    }

    /**
     * Архівувати чернетку
     *
     * @OA\Post(
     *     path="/v1/my/drafts/{id}/archive",
     *     operationId="archiveDraft",
     *     tags={"Drafts"},
     *     summary="Архівувати чернетку",
     *     description="Змінює статус чернетки на archived",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID чернетки", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Чернетку архівовано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="draft", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function archive(int $id): JsonResponse
    {
        $user = Auth::user();

        $draft = ProjectDraft::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [ProjectDraft::STATUS_DELETED, ProjectDraft::STATUS_ARCHIVED])
            ->findOrFail($id);

        $draft->update([
            'status' => ProjectDraft::STATUS_ARCHIVED,
        ]);

        return response()->json([
            'result' => true,
            'message' => 'Чернетку архівовано',
            'data' => [
                'draft' => $this->formatDraft($draft->fresh()),
            ],
        ]);
    }

    /**
     * Форматування чернетки для відповіді
     *
     * @return array<string, mixed>
     */
    private function formatDraft(ProjectDraft $draft): array
    {
        return [
            'id' => $draft->id,
            'project_id' => $draft->project_id,
            'status' => $draft->status,
            'data' => $draft->data,
            'created_at' => $draft->created_at->toIso8601String(),
            'updated_at' => $draft->updated_at->toIso8601String(),
        ];
    }
}
