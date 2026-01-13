<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateBonusRequest;
use App\Http\Requests\Api\V1\UpdateBonusRequest;
use App\Http\Resources\Api\V1\ProjectBonusResource;
use App\Models\Project;
use App\Models\ProjectBonus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

class ProjectBonusController extends Controller
{
    /**
     * Отримати список бонусів проєкту
     *
     * @OA\Get(
     *     path="/v1/my/projects/{project}/bonuses",
     *     operationId="getProjectBonuses",
     *     tags={"Project Bonuses"},
     *     summary="Список бонусів проекту",
     *     description="Повертає всі бонуси для проекту",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="ID проекту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список бонусів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        $bonuses = $project->bonuses()->orderBy('order')->get();

        return ProjectBonusResource::collection($bonuses);
    }

    /**
     * Створити новий бонус
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/bonuses",
     *     operationId="createProjectBonus",
     *     tags={"Project Bonuses"},
     *     summary="Створити бонус",
     *     description="Створює новий бонус для проекту",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="ID проекту", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="object",
     *                 @OA\Property(property="uk", type="string"),
     *                 @OA\Property(property="en", type="string")
     *             ),
     *             @OA\Property(property="description", type="object"),
     *             @OA\Property(property="min_amount", type="number"),
     *             @OA\Property(property="max_amount", type="number"),
     *             @OA\Property(property="quantity", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Бонус створено",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error або проект не можна редагувати")
     * )
     */
    public function store(CreateBonusRequest $request, Project $project): ProjectBonusResource|JsonResponse
    {
        if (! $project->isEditable()) {
            return response()->json(['message' => 'Проєкт не можна редагувати'], 422);
        }

        $data = $request->validated();

        $maxOrder = $project->bonuses()->max('order') ?? 0;

        $bonus = $project->bonuses()->create([
            ...$data,
            'order' => $maxOrder + 1,
            'quantity_claimed' => 0,
        ]);

        return new ProjectBonusResource($bonus);
    }

    /**
     * Оновити бонус
     *
     * @OA\Put(
     *     path="/v1/my/projects/{project}/bonuses/{bonus}",
     *     operationId="updateProjectBonus",
     *     tags={"Project Bonuses"},
     *     summary="Оновити бонус",
     *     description="Оновлює існуючий бонус проекту",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="ID проекту", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="bonus", in="path", required=true, description="ID бонуса", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="object"),
     *             @OA\Property(property="description", type="object"),
     *             @OA\Property(property="min_amount", type="number"),
     *             @OA\Property(property="max_amount", type="number"),
     *             @OA\Property(property="quantity", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Бонус оновлено",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateBonusRequest $request, Project $project, ProjectBonus $bonus): ProjectBonusResource|JsonResponse
    {
        if ($bonus->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validated();

        $bonus->update($data);

        return new ProjectBonusResource($bonus);
    }

    /**
     * Видалити бонус
     *
     * @OA\Delete(
     *     path="/v1/my/projects/{project}/bonuses/{bonus}",
     *     operationId="deleteProjectBonus",
     *     tags={"Project Bonuses"},
     *     summary="Видалити бонус",
     *     description="Видаляє бонус проекту. Не можна видалити бонус, який вже обрали донатери.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="ID проекту", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="bonus", in="path", required=true, description="ID бонуса", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Бонус видалено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Бонус вже використаний")
     * )
     */
    public function destroy(Request $request, Project $project, ProjectBonus $bonus): JsonResponse
    {
        // Перевіряємо доступ
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($bonus->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $project->isEditable()) {
            return response()->json(['message' => 'Проєкт не можна редагувати'], 422);
        }

        // Не дозволяємо видаляти бонуси, які вже використані
        if ($bonus->quantity_claimed > 0) {
            return response()->json([
                'message' => 'Неможливо видалити бонус, який вже обрали донатери.',
            ], 422);
        }

        $bonus->delete();

        return response()->json(['message' => 'Бонус видалено']);
    }
}
