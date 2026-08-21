<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateStageRequest;
use App\Http\Requests\Api\V1\UpdateStageRequest;
use App\Http\Resources\Api\V1\ProjectStageResource;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Services\ProjectWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

class ProjectStageController extends Controller
{
    /**
     * Отримати список етапів проєкту
     *
     * @OA\Get(
     *     path="/v1/my/projects/{project}/stages",
     *     operationId="getProjectStages",
     *     tags={"Project Stages"},
     *     summary="Отримати список етапів проєкту (03.4.2.6)",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         description="ID проєкту",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список етапів",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/ProjectStage")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Проєкт не знайдено")
     * )
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        $stages = $project->stages()->orderBy('order')->get();

        return ProjectStageResource::collection($stages);
    }

    /**
     * Створити новий етап
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/stages",
     *     operationId="createProjectStage",
     *     tags={"Project Stages"},
     *     summary="Створити новий етап проєкту (03.4.2.6)",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         description="ID проєкту",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"title"},
     *
     *             @OA\Property(property="title", ref="#/components/schemas/LocalizedString", example={"uk": "Закупівля матеріалів", "en": "Purchasing materials"}),
     *             @OA\Property(property="description", ref="#/components/schemas/LocalizedString", nullable=true, example={"uk": "Опис етапу", "en": "Stage description"}),
     *             @OA\Property(property="days_planned", type="integer", example=14, description="Планова кількість днів"),
     *             @OA\Property(property="budget_planned", type="number", format="float", example=5000.00, description="Плановий бюджет")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Етап створено",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProjectStage")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function store(CreateStageRequest $request, Project $project): ProjectStageResource|JsonResponse
    {
        // Оголошений/в роботі/на паузі: додавання нових етапів дозволене (docs/project-lifecycle-flow.md
        // — "Додавання Етапів"), на відміну від повного редагування (isEditable — лише new/draft).
        if (! $project->canManageStagesByOwner()) {
            return response()->json(['message' => 'Проєкт не можна редагувати'], 422);
        }

        $this->returnPendingModerationToDraft($project);

        $data = $request->validated();

        $maxOrder = $project->stages()->max('order') ?? 0;

        $stage = $project->stages()->create([
            ...$data,
            'order' => $maxOrder + 1,
            'status' => StageStatus::Planned,
        ]);

        return new ProjectStageResource($stage);
    }

    /**
     * Оновити етап
     *
     * @OA\Put(
     *     path="/v1/my/projects/{project}/stages/{stage}",
     *     operationId="updateProjectStage",
     *     tags={"Project Stages"},
     *     summary="Оновити етап проєкту (03.4.2.6)",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="stage",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="description", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="status", type="string", enum={"planned", "in_progress", "completed"}),
     *             @OA\Property(property="days_planned", type="integer"),
     *             @OA\Property(property="budget_planned", type="number", format="float"),
     *             @OA\Property(property="budget_actual", type="number", format="float"),
     *             @OA\Property(property="order", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Етап оновлено",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProjectStage")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateStageRequest $request, Project $project, ProjectStage $stage): ProjectStageResource|JsonResponse
    {
        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $project->canManageStagesByOwner()) {
            return response()->json(['message' => 'Етапи проєкту не можна редагувати у поточному статусі'], 422);
        }

        $this->returnPendingModerationToDraft($project);

        $data = $request->validated();

        // Назву та опис можна редагувати лише поки етап ще запланований — інакше клієнт міг би
        // обійти "read-only" поля напряму через API, тож перевірка продубльована на сервері.
        if ($stage->status !== StageStatus::Planned && (array_key_exists('title', $data) || array_key_exists('description', $data))) {
            return response()->json(['message' => 'Назву та опис етапу можна редагувати лише поки він запланований'], 422);
        }

        $newStatus = array_key_exists('status', $data)
            ? ($data['status'] instanceof StageStatus ? $data['status'] : StageStatus::from($data['status']))
            : null;

        if ($newStatus !== null && $newStatus !== $stage->status) {
            $allowedNextStatuses = [
                StageStatus::Planned->value => StageStatus::InProgress,
                StageStatus::InProgress->value => StageStatus::Completed,
            ];

            if (($allowedNextStatuses[$stage->status->value] ?? null) !== $newStatus) {
                return response()->json(['message' => 'Недопустима зміна статусу етапу'], 422);
            }

            if ($newStatus === StageStatus::InProgress) {
                $daysPlanned = $data['days_planned'] ?? $stage->days_planned;
                $budgetPlanned = $data['budget_planned'] ?? $stage->budget_planned;

                if (! $daysPlanned || ! $budgetPlanned) {
                    return response()->json(['message' => 'Вкажіть кількість днів та плановану суму перед початком етапу'], 422);
                }

                $data['started_at'] = now();
            }

            if ($newStatus === StageStatus::Completed) {
                $budgetActual = $data['budget_actual'] ?? $stage->budget_actual;

                if (! $budgetActual) {
                    return response()->json(['message' => 'Вкажіть фактичну суму витрат перед завершенням етапу'], 422);
                }

                if (empty($stage->documents)) {
                    return response()->json(['message' => 'Додайте хоча б один файл-підтвердження перед завершенням етапу'], 422);
                }

                $data['completed_at'] = now();
            }
        } elseif ($stage->status !== StageStatus::Planned) {
            // Етап уже розпочато/завершено — планові поля більше не редагуються без зміни статусу.
            unset($data['days_planned'], $data['budget_planned']);
        }

        $stage->update($data);

        return new ProjectStageResource($stage);
    }

    /**
     * Видалити етап
     *
     * @OA\Delete(
     *     path="/v1/my/projects/{project}/stages/{stage}",
     *     operationId="deleteProjectStage",
     *     tags={"Project Stages"},
     *     summary="Видалити етап проєкту",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="stage",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Етап видалено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Етап видалено")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Проєкт не можна редагувати")
     * )
     */
    public function destroy(Request $request, Project $project, ProjectStage $stage): JsonResponse
    {
        // Перевіряємо доступ
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $project->canBeFullyEditedByOwner()) {
            return response()->json(['message' => 'Проєкт не можна редагувати'], 422);
        }

        $this->returnPendingModerationToDraft($project);

        $stage->delete();

        return response()->json(['message' => 'Етап видалено']);
    }

    /**
     * Почати виконання етапу
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/stages/{stage}/start",
     *     operationId="startProjectStage",
     *     tags={"Project Stages"},
     *     summary="Почати виконання етапу (03.4.2.6)",
     *     description="Змінює статус етапу на 'in_progress' та встановлює дату початку",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="stage",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Етап розпочато",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProjectStage")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function start(Request $request, Project $project, ProjectStage $stage): ProjectStageResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $project->isPartiallyEditable()) {
            return response()->json(['message' => 'Почати етап можна лише після публікації проєкту'], 422);
        }

        if ($stage->status !== StageStatus::Planned) {
            return response()->json(['message' => 'Почати можна лише запланований етап'], 422);
        }

        if (! $stage->days_planned || ! $stage->budget_planned) {
            return response()->json(['message' => 'Вкажіть кількість днів та плановану суму перед початком етапу'], 422);
        }

        $stage->update([
            'status' => StageStatus::InProgress,
            'started_at' => now(),
        ]);

        return new ProjectStageResource($stage);
    }

    /**
     * Завершити етап
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/stages/{stage}/complete",
     *     operationId="completeProjectStage",
     *     tags={"Project Stages"},
     *     summary="Завершити етап (03.4.2.6)",
     *     description="Змінює статус етапу на 'completed' та встановлює дату завершення",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="stage",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Етап завершено",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProjectStage")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function complete(Request $request, Project $project, ProjectStage $stage): ProjectStageResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $project->isPartiallyEditable()) {
            return response()->json(['message' => 'Завершити етап можна лише в опублікованому проєкті'], 422);
        }

        if ($stage->status !== StageStatus::InProgress) {
            return response()->json(['message' => 'Завершити можна лише етап у роботі'], 422);
        }

        if (! $stage->budget_actual) {
            return response()->json(['message' => 'Вкажіть фактичну суму витрат перед завершенням етапу'], 422);
        }

        if (empty($stage->documents)) {
            return response()->json(['message' => 'Додайте хоча б один файл-підтвердження перед завершенням етапу'], 422);
        }

        $stage->update([
            'status' => StageStatus::Completed,
            'completed_at' => now(),
        ]);

        return new ProjectStageResource($stage);
    }

    /**
     * Завантажити документи/фото для етапу
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/stages/{stage}/documents",
     *     operationId="uploadStageDocuments",
     *     tags={"Project Stages"},
     *     summary="Завантажити документи/фото для етапу (03.4.2.6)",
     *     description="Завантажує фото-звіти, чеки та інші документи для підтвердження виконання етапу",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         description="ID проєкту",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="stage",
     *         in="path",
     *         required=true,
     *         description="ID етапу",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"documents"},
     *
     *                 @OA\Property(
     *                     property="documents",
     *                     type="array",
     *                     description="Файли документів (до 10 файлів, до 5MB кожен)",
     *
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="descriptions",
     *                     type="array",
     *                     description="Описи для кожного файлу",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="uk", type="string", example="Чек за матеріали"),
     *                         @OA\Property(property="en", type="string", example="Receipt for materials")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Документи завантажено",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProjectStage")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function uploadDocuments(Request $request, Project $project, ProjectStage $stage): ProjectStageResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $project->canManageStagesByOwner()) {
            return response()->json(['message' => 'Документи етапу не можна редагувати у поточному статусі'], 422);
        }

        $this->returnPendingModerationToDraft($project);

        $request->validate([
            'documents' => ['required', 'array', 'max:10'],
            'documents.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'array'],
            'descriptions.*.uk' => ['nullable', 'string', 'max:255'],
            'descriptions.*.en' => ['nullable', 'string', 'max:255'],
        ], [
            'documents.required' => 'Завантажте хоча б один документ',
            'documents.max' => 'Максимум 10 документів',
            'documents.*.mimes' => 'Дозволені формати: JPG, PNG, PDF',
            'documents.*.max' => 'Максимальний розмір файлу 5MB',
        ]);

        $existingDocuments = $stage->documents ?? [];
        $newDocuments = [];
        $descriptions = $request->input('descriptions', []);

        foreach ($request->file('documents') as $index => $file) {
            $path = $file->store("projects/{$project->id}/stages/{$stage->id}", 'public');

            $newDocuments[] = [
                'type' => $file->getClientOriginalExtension() === 'pdf' ? 'document' : 'photo',
                'file' => $path,
                'file_url' => Storage::disk('public')->url($path),
                'original_name' => $file->getClientOriginalName(),
                'description' => $descriptions[$index] ?? null,
                'uploaded_at' => now()->toISOString(),
            ];
        }

        $stage->update([
            'documents' => array_merge($existingDocuments, $newDocuments),
        ]);

        return new ProjectStageResource($stage->fresh());
    }

    /**
     * Видалити документ з етапу
     *
     * @OA\Delete(
     *     path="/v1/my/projects/{project}/stages/{stage}/documents/{documentIndex}",
     *     operationId="deleteStageDocument",
     *     tags={"Project Stages"},
     *     summary="Видалити документ з етапу",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="stage",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="documentIndex",
     *         in="path",
     *         required=true,
     *         description="Індекс документа в масиві (починаючи з 0)",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Документ видалено",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProjectStage")
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deleteDocument(Request $request, Project $project, ProjectStage $stage, int $documentIndex): ProjectStageResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $project->canManageStagesByOwner()) {
            return response()->json(['message' => 'Документи етапу не можна редагувати у поточному статусі'], 422);
        }

        $this->returnPendingModerationToDraft($project);

        $documents = $stage->documents ?? [];

        if (! isset($documents[$documentIndex])) {
            return response()->json(['message' => 'Документ не знайдено'], 404);
        }

        // Видаляємо файл зі сховища
        if (isset($documents[$documentIndex]['file'])) {
            Storage::disk('public')->delete($documents[$documentIndex]['file']);
        }

        // Видаляємо з масиву
        array_splice($documents, $documentIndex, 1);

        $stage->update([
            'documents' => $documents,
        ]);

        return new ProjectStageResource($stage->fresh());
    }

    private function returnPendingModerationToDraft(Project $project): void
    {
        if ($project->status === ProjectStatus::Moderation) {
            app(ProjectWorkflowService::class)->returnToDraft($project);
        }
    }
}
