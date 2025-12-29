<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MyProjectController extends Controller
{
    /**
     * Отримати список власних проєктів
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::query()
            ->with('user')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Фільтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    /**
     * Створити новий проєкт (чернетку)
     */
    public function store(CreateProjectRequest $request): ProjectResource
    {
        $data = $request->validated();

        // Генеруємо унікальний код та slug
        $data['code'] = strtoupper(Str::random(8));
        $data['slug'] = Str::slug($data['title']['uk'] ?? 'project').'-'.Str::random(6);

        // Обробка обкладинки
        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        }

        // Встановлюємо початкові статуси
        $data['user_id'] = $request->user()->id;
        $data['status'] = ProjectStatus::Draft;
        $data['status_moderation'] = ModerationStatus::Pending;
        $data['budget_collected'] = 0;
        $data['likes_count'] = 0;
        $data['donors_count'] = 0;

        $project = Project::create($data);

        return new ProjectResource($project->load(['user', 'stages', 'bonuses']));
    }

    /**
     * Отримати деталі власного проєкту
     */
    public function show(Request $request, Project $project): ProjectResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new ProjectResource($project->load(['user', 'stages', 'bonuses']));
    }

    /**
     * Оновити проєкт
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $data = $request->validated();

        // Обробка обкладинки
        if ($request->hasFile('cover')) {
            // Видаляємо стару обкладинку
            if ($project->cover) {
                Storage::disk('public')->delete($project->cover);
            }
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        }

        $project->update($data);

        return new ProjectResource($project->load(['user', 'stages', 'bonuses']));
    }

    /**
     * Видалити проєкт (тільки чернетки)
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($project->status !== ProjectStatus::Draft) {
            return response()->json([
                'message' => 'Можна видалити лише чернетки. Для інших проєктів зверніться до модератора.',
            ], 422);
        }

        // Видаляємо обкладинку
        if ($project->cover) {
            Storage::disk('public')->delete($project->cover);
        }

        $project->delete();

        return response()->json(['message' => 'Проєкт видалено']);
    }

    /**
     * Відправити проєкт на модерацію
     */
    public function submit(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Rejected])) {
            return response()->json([
                'message' => 'На модерацію можна відправити лише чернетку або відхилений проєкт.',
            ], 422);
        }

        // Валідація обов'язкових полів перед відправкою
        $errors = [];
        if (empty($project->title['uk'])) {
            $errors['title'] = ['Назва проєкту є обов\'язковою'];
        }
        if (empty($project->art_category)) {
            $errors['art_category'] = ['Оберіть галузь мистецтва'];
        }
        if (empty($project->budget_goal)) {
            $errors['budget_goal'] = ['Вкажіть ціль збору'];
        }

        if (! empty($errors)) {
            return response()->json([
                'message' => 'Заповніть обов\'язкові поля перед відправкою на модерацію.',
                'errors' => $errors,
            ], 422);
        }

        $project->update([
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Pending,
        ]);

        return response()->json([
            'message' => 'Проєкт відправлено на модерацію.',
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Завершити проєкт (додати фінальний результат)
     */
    public function complete(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($project->status !== ProjectStatus::InProgress) {
            return response()->json([
                'message' => 'Завершити можна лише проєкт в роботі.',
            ], 422);
        }

        $request->validate([
            'final_result' => ['required', 'array'],
            'final_result.type' => ['required', 'in:image,gallery,video,link'],
        ]);

        $project->update([
            'status' => ProjectStatus::Completed,
            'final_result' => $request->input('final_result'),
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Проєкт завершено.',
            'data' => new ProjectResource($project),
        ]);
    }
}
