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

class ProjectBonusController extends Controller
{
    /**
     * Отримати список бонусів проєкту
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        $bonuses = $project->bonuses()->orderBy('order')->get();

        return ProjectBonusResource::collection($bonuses);
    }

    /**
     * Створити новий бонус
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
