<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicUserController extends Controller
{
    /**
     * Отримати публічний профіль користувача
     */
    public function show(int $id): JsonResponse
    {
        $user = User::query()
            ->with(['profilePersonal', 'profileSocial'])
            ->findOrFail($id);

        // Рахуємо статистику
        $projectsCount = $user->projects()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->count();

        $totalCollected = $user->projects()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->sum('budget_collected');

        $supportersCount = $user->projects()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->sum('donors_count');

        $personal = $user->profilePersonal;
        $social = $user->profileSocial;

        return response()->json([
            'result' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $personal?->avatar ?? null,
                    'role' => $user->role->value ?? 'user',
                    'profession' => $personal?->profession ?? null,
                    'description' => $personal?->about ?? null,
                    'country' => $personal?->country ?? null,
                    'city' => $personal?->city ?? null,
                    'social_links' => [
                        'website' => $social?->website ?? null,
                        'instagram' => $social?->instagram ?? null,
                        'facebook' => $social?->facebook ?? null,
                        'youtube' => $social?->youtube ?? null,
                        'tiktok' => $social?->tiktok ?? null,
                    ],
                    'statistics' => [
                        'projects_count' => $projectsCount,
                        'total_collected' => (float) $totalCollected,
                        'supporters_count' => $supportersCount,
                    ],
                    'member_since' => $user->created_at->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Отримати публічні проєкти користувача
     */
    public function projects(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $perPage = min((int) $request->input('per_page', 10), 50);

        $projects = $user->projects()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->with(['user.profilePersonal'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $formattedProjects = $projects->getCollection()->map(function (Project $project) {
            return [
                'id' => $project->id,
                'slug' => $project->slug,
                'name' => $project->title,
                'collected' => (float) $project->budget_collected,
                'need_to_collect' => (float) $project->budget_goal,
                'people_supported' => $project->donors_count,
                'project_image' => $project->cover,
                'project_status' => [
                    'key' => $project->status->value,
                    'uk' => $project->status->getLabel(),
                    'en' => $project->status->value,
                ],
                'art_form' => $project->art_category,
                'art_subform' => $project->art_subcategory,
                'date_of_announcement' => $project->announced_at?->toDateString(),
            ];
        });

        return response()->json([
            'result' => true,
            'data' => [
                'projects' => $formattedProjects,
                'pagination' => [
                    'current_page' => $projects->currentPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                    'total_pages' => $projects->lastPage(),
                    'has_next' => $projects->hasMorePages(),
                    'has_prev' => $projects->currentPage() > 1,
                ],
            ],
        ]);
    }
}
