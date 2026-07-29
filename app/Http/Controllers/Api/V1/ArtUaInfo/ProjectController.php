<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ProjectSource;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ArtUaInfo\StoreProjectRequest;
use App\Http\Requests\Api\V1\ArtUaInfo\UpdateProjectRequest;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\ArtCategory;
use App\Models\Project;
use App\Services\ImageProcessingService;
use App\Support\ProjectCategoryParameterValues;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="ArtUaInfo Projects",
 *     description="API для створення проєктів з візарда art-ua-info (без бюджету/етапів/бонусів)"
 * )
 */
class ProjectController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessor,
    ) {}

    /**
     * Створити проєкт з візарда art-ua-info
     *
     * @OA\Post(
     *     path="/v1/art-ua-info/projects",
     *     operationId="artUaInfoCreateProject",
     *     tags={"ArtUaInfo Projects"},
     *     summary="Створити проєкт з art-ua-info",
     *     security={{"bearerAuth":{}, "apiKey":{}}},
     *
     *     @OA\Response(response=200, description="Проєкт створено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Project"))),
     *     @OA\Response(response=401, description="Не авторизований"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(StoreProjectRequest $request): ProjectResource
    {
        $data = $request->validated();
        $status = $request->getProjectStatus();

        if (isset($data['art_category'])) {
            $data['art_category_id'] = ArtCategory::resolveIdFromSlugs(
                $data['art_category'] ?? null,
                $data['art_subcategory'] ?? null
            );
        }
        unset($data['art_category'], $data['art_subcategory'], $data['local_id']);

        $parametersData = $data['parameters'] ?? null;
        unset($data['parameters']);

        if (isset($data['cover'])) {
            $data['cover'] = $this->imageProcessor->processCover($data['cover']);
        }

        if (isset($data['final_result'])) {
            $data['final_result'] = $this->imageProcessor->processContentBlocks($data['final_result']);
        }

        if (isset($data['content_blocks'])) {
            $data['content_blocks'] = $this->imageProcessor->processContentBlocks($data['content_blocks']);
        }

        $userType = $data['user_type'] ?? null;

        $data['user_id'] = $request->user()->id;
        $data['status'] = $status;
        $data['status_moderation'] = $request->isDraftSave() ? ModerationStatus::Pending : ModerationStatus::Approved;
        $data['source'] = ProjectSource::ArtUaInfo;
        $data['is_legal'] = $userType === UserType::Legal->value;
        $data['currency'] = $data['currency'] ?? Currency::USD->value;
        $data['budget_goal'] = 0;
        $data['budget_collected'] = 0;
        $data['likes_count'] = 0;
        $data['donors_count'] = 0;

        $project = Project::create($data);

        ProjectCategoryParameterValues::syncForProject($project, $parametersData);

        return new ProjectResource($project->load(['user.profileLegal', 'projectParameters.parameter', 'projectParameters.parameterValue']));
    }

    /**
     * Редагувати вже створений проєкт з візарда art-ua-info. Статус (completed/approved)
     * редагування не чіпає — оновлюється лише вміст.
     *
     * @OA\Put(
     *     path="/v1/art-ua-info/projects/{project}",
     *     operationId="artUaInfoUpdateProject",
     *     tags={"ArtUaInfo Projects"},
     *     summary="Редагувати проєкт з art-ua-info",
     *     security={{"bearerAuth":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Проєкт оновлено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Project"))),
     *     @OA\Response(response=401, description="Не авторизований"),
     *     @OA\Response(response=403, description="Не власник проєкту"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $data = $request->validated();

        if (isset($data['art_category'])) {
            $data['art_category_id'] = ArtCategory::resolveIdFromSlugs(
                $data['art_category'] ?? null,
                $data['art_subcategory'] ?? null
            );
        }
        unset($data['art_category'], $data['art_subcategory']);

        $parametersData = $data['parameters'] ?? null;
        unset($data['parameters']);

        if (isset($data['cover'])) {
            $data['cover'] = $this->imageProcessor->processCover($data['cover'], $project->cover);
        }

        if (isset($data['final_result'])) {
            $data['final_result'] = $this->imageProcessor->processContentBlocks($data['final_result'], $project->final_result);
        }

        if (isset($data['content_blocks'])) {
            $data['content_blocks'] = $this->imageProcessor->processContentBlocks($data['content_blocks'], $project->content_blocks);
        }

        $userType = $data['user_type'] ?? null;
        $data['is_legal'] = $userType === UserType::Legal->value;

        $project->update($data);

        ProjectCategoryParameterValues::syncForProject($project, $parametersData);

        return new ProjectResource($project->fresh()->load(['user.profileLegal', 'projectParameters.parameter', 'projectParameters.parameterValue']));
    }
}
