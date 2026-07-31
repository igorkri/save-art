<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTeamRequest;
use App\Http\Requests\Api\V1\UpdateTeamRequest;
use App\Http\Resources\Api\V1\TeamMemberResource;
use App\Http\Resources\Api\V1\TeamResource;
use App\Models\Team;
use App\Models\User;
use App\Services\ImageProcessingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * API для управління власними командами (кабінет митця, art-ua-info)
 *
 * @OA\Tag(
 *     name="My Teams",
 *     description="API для управління власними творчими командами (art-ua-info)"
 * )
 */
class MyTeamController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ImageProcessingService $imageProcessor
    ) {}

    /**
     * Список команд, де користувач є учасником
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/my/teams",
     *     operationId="artUaInfoGetMyTeams",
     *     tags={"My Teams"},
     *     summary="Список моїх команд",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(response=200, description="Список команд", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Team")))),
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $teams = $request->user()->teams()->with('members')->get();

        return TeamResource::collection($teams);
    }

    /**
     * Пошук користувачів для додавання в команду (за ПІБ або email).
     *
     * Приватний ендпоінт (на відміну від публічного /artists) — тому дозволяє
     * шукати за email і не вимагає наявності опублікованих проєктів.
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/my/teams/search-members",
     *     operationId="artUaInfoSearchTeamMembers",
     *     tags={"My Teams"},
     *     summary="Пошук користувачів для додавання в команду",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="search", in="query", required=true, description="ПІБ або email", @OA\Schema(type="string"), example="Іван"),
     *
     *     @OA\Response(response=200, description="Список користувачів", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TeamMember")))),
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function searchMembers(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search'));

        if ($search === '') {
            return TeamMemberResource::collection([]);
        }

        $users = User::query()
            ->where('is_blocked', false)
            ->where('id', '!=', $request->user()->id)
            ->where(function ($query) use ($search) {
                $query->where('full_name->uk', 'like', "%{$search}%")
                    ->orWhere('full_name->en', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return TeamMemberResource::collection($users);
    }

    /**
     * Створити команду (поточний користувач стає власником)
     *
     * @OA\Post(
     *     path="/v1/art-ua-info/my/teams",
     *     operationId="artUaInfoCreateMyTeam",
     *     tags={"My Teams"},
     *     summary="Створити команду",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="avatar", type="string", nullable=true, description="Файл або Base64 data URL"),
     *             @OA\Property(property="website", type="string", nullable=true),
     *             @OA\Property(property="country", ref="#/components/schemas/LocalizedString", nullable=true),
     *             @OA\Property(property="city", ref="#/components/schemas/LocalizedString", nullable=true),
     *             @OA\Property(property="description", ref="#/components/schemas/LocalizedString", nullable=true),
     *             @OA\Property(property="members", type="array", @OA\Items(type="object", @OA\Property(property="user_id", type="integer")))
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Команду створено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Team"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(StoreTeamRequest $request): TeamResource
    {
        $data = $request->validated();
        $members = $data['members'] ?? [];
        unset($data['members']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('teams', 'public');
        } elseif (! empty($data['avatar'])) {
            $data['avatar'] = $this->imageProcessor->saveBase64Image($data['avatar'], 'teams');
        }

        $data['slug'] = Str::slug($data['name']['uk']).'-'.Str::random(6);

        $team = DB::transaction(function () use ($data, $members, $request) {
            $team = Team::create($data);

            $team->teamMembers()->create([
                'user_id' => $request->user()->id,
                'role' => 'owner',
                'sort_order' => 0,
            ]);

            foreach ($members as $index => $member) {
                if ((int) $member['user_id'] === $request->user()->id) {
                    continue;
                }
                $team->teamMembers()->create([
                    'user_id' => $member['user_id'],
                    'role' => 'member',
                    'sort_order' => $index + 1,
                ]);
            }

            return $team;
        });

        return new TeamResource($team->load('members'));
    }

    /**
     * Оновити команду (лише власник)
     *
     * @OA\Put(
     *     path="/v1/art-ua-info/my/teams/{team}",
     *     operationId="artUaInfoUpdateMyTeam",
     *     tags={"My Teams"},
     *     summary="Оновити команду",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Команду оновлено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Team"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник команди"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        $this->authorize('update', $team);

        $data = $request->validated();
        $members = $data['members'] ?? null;
        unset($data['members']);

        if ($request->hasFile('avatar')) {
            if ($team->avatar) {
                Storage::disk('public')->delete($team->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('teams', 'public');
        } elseif (! empty($data['avatar'])) {
            $data['avatar'] = $this->imageProcessor->processCover($data['avatar'], $team->avatar, 'teams');
        }

        $team->fill($data);
        $team->save();

        if ($members !== null) {
            $ownerId = $request->user()->id;
            $team->teamMembers()->where('user_id', '!=', $ownerId)->delete();

            foreach ($members as $index => $member) {
                if ((int) $member['user_id'] === $ownerId) {
                    continue;
                }
                $team->teamMembers()->create([
                    'user_id' => $member['user_id'],
                    'role' => 'member',
                    'sort_order' => $index + 1,
                ]);
            }
        }

        return new TeamResource($team->load('members'));
    }

    /**
     * Видалити команду (лише власник)
     *
     * @OA\Delete(
     *     path="/v1/art-ua-info/my/teams/{team}",
     *     operationId="artUaInfoDeleteMyTeam",
     *     tags={"My Teams"},
     *     summary="Видалити команду",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Команду видалено"),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник команди")
     * )
     */
    public function destroy(Request $request, Team $team): JsonResponse
    {
        $this->authorize('delete', $team);

        if ($team->avatar) {
            Storage::disk('public')->delete($team->avatar);
        }
        $team->teamMembers()->delete();
        $team->delete();

        return response()->json(['message' => 'Команду видалено']);
    }

    /**
     * Вийти з команди (для учасника, що не є власником)
     *
     * @OA\Post(
     *     path="/v1/art-ua-info/my/teams/{team}/leave",
     *     operationId="artUaInfoLeaveMyTeam",
     *     tags={"My Teams"},
     *     summary="Покинути команду",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="team", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Ви покинули команду"),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=404, description="Ви не є учасником цієї команди"),
     *     @OA\Response(response=422, description="Власник не може покинути команду")
     * )
     */
    public function leave(Request $request, Team $team): JsonResponse
    {
        $membership = $team->teamMembers()->where('user_id', $request->user()->id)->first();
        abort_if(! $membership, 404, 'Ви не є учасником цієї команди');
        abort_if($membership->role === 'owner', 422, 'Власник не може покинути команду, лише видалити її');

        $membership->delete();

        return response()->json(['message' => 'Ви покинули команду']);
    }
}
