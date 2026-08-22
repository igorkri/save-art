<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Message;
use App\Models\Project;
use App\Services\ModerationService;
use App\Services\ProjectWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Модерація проєктів прямо з публічної сторінки сайту (art-ua-info / save-art),
 * як альтернатива діям у Filament-таблиці. Доступ обмежено middleware `can.moderate`.
 */
class ModerationController extends Controller
{
    public function __construct(
        private ModerationService $moderationService,
        private ProjectWorkflowService $workflowService
    ) {}

    /**
     * Взяти проєкт у розгляд (Pending -> Processing)
     */
    public function startReview(Request $request, Project $project): JsonResponse
    {
        if (! $this->workflowService->startReview($project)) {
            return response()->json([
                'message' => 'Проєкт вже не очікує на модерацію.',
            ], 422);
        }

        return response()->json([
            'message' => 'Проєкт взято в розгляд.',
            'status_moderation' => $project->refresh()->status_moderation->value,
        ]);
    }

    /**
     * Схвалити проєкт
     */
    public function approve(Request $request, Project $project): JsonResponse
    {
        if (! $this->moderationService->approveProject($project, $request->user())) {
            return response()->json([
                'message' => 'Спочатку візьміть проєкт у розгляд.',
            ], 422);
        }

        return response()->json([
            'message' => 'Проєкт схвалено.',
            'status' => $project->refresh()->status->value,
        ]);
    }

    /**
     * Відхилити проєкт
     */
    public function reject(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! $this->moderationService->rejectProject($project, $request->user(), $validated['reason'])) {
            return response()->json([
                'message' => 'Спочатку візьміть проєкт у розгляд.',
            ], 422);
        }

        return response()->json([
            'message' => 'Проєкт відхилено.',
            'status' => $project->refresh()->status->value,
        ]);
    }

    /**
     * Повернути проєкт на доопрацювання: не остаточна відмова (як reject), а
     * повернення в чернетку — автор виправляє зауваження й надсилає повторно.
     */
    public function returnForRevision(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        if (! $this->moderationService->returnForRevision($project, $request->user(), $validated['comment'])) {
            return response()->json([
                'message' => 'Проєкт не можна повернути на доопрацювання з поточного статусу.',
            ], 422);
        }

        return response()->json([
            'message' => 'Проєкт повернуто на доопрацювання.',
            'status' => $project->refresh()->status->value,
        ]);
    }

    /**
     * Написати автору проєкту (той самий канал, що й "Написати автору" у Filament) —
     * доступно незалежно від статусу модерації, щоб можна було уточнити деталі
     * ще до того, як проєкт узятий у розгляд.
     */
    public function message(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $chatMessage = Message::create([
            'user_id' => $project->user_id,
            'admin_id' => $request->user()->id,
            'project_id' => $project->id,
            'subject' => $validated['subject'] ?? null,
            'content' => $validated['content'],
            'direction' => Message::DIRECTION_ADMIN_TO_USER,
        ]);

        return response()->json([
            'message' => 'Повідомлення надіслано.',
            'data' => new MessageResource($chatMessage),
        ], 201);
    }
}
