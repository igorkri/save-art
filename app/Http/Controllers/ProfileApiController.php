<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProfilePersonal;
use App\Models\ProfileLegal;
use App\Models\ProfileSocial;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateProfilePersonalRequest;
use App\Http\Requests\UpdateProfileLegalRequest;
use App\Http\Requests\UpdateProfileSocialRequest;
use App\Http\Resources\ProfilePersonalResource;
use App\Http\Resources\ProfileLegalResource;
use Illuminate\Support\Facades\Auth;

class ProfileApiController extends Controller
{
    /**
     * Получить профиль текущего пользователя
     */
    public function getProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['profilePersonal', 'profileLegal', 'profileSocial']);

        return response()->json([
            'user' => $user,
            'profilePersonal' => $user->profilePersonal ? new ProfilePersonalResource($user->profilePersonal) : null,
            'profileLegal' => $user->profileLegal ? new ProfileLegalResource($user->profileLegal) : null,
            'profileSocial' => $user->profileSocial,
        ]);
    }

    /**
     * Обновить личный профиль пользователя
     */
    public function updatePersonal(UpdateProfilePersonalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $profile = $user->profilePersonal;
        if (!$profile) {
            $profile = new ProfilePersonal(['user_id' => $user->id]);
        }
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profilePersonal' => new ProfilePersonalResource($profile)]);
    }

    /**
     * Создать личный профиль пользователя
     */
    public function createPersonal(UpdateProfilePersonalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user->profilePersonal()->exists()) {
            return response()->json(['message' => __('api.profile.personal_exists')], 409);
        }
        $profile = new ProfilePersonal(['user_id' => $user->id]);
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profilePersonal' => new ProfilePersonalResource($profile)], 201);
    }

    /**
     * Обновить юридический профиль пользователя
     */
    public function updateLegal(UpdateProfileLegalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $profile = $user->profileLegal;
        if (!$profile) {
            $profile = new ProfileLegal(['user_id' => $user->id]);
        }
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profileLegal' => new ProfileLegalResource($profile)]);
    }

    /**
     * Создать юридический профиль пользователя
     */
    public function createLegal(UpdateProfileLegalRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if ($user->profileLegal()->exists()) {
            return response()->json(['message' => __('api.profile.legal_exists')], 409);
        }

        $profile = new ProfileLegal(['user_id' => $user->id]);
        $profile->fill($request->validated());
        $profile->save();

        return response()->json(['profileLegal' => new ProfileLegalResource($profile)], 201);
    }

    /**
     * Обновить социальный профиль пользователя
     */
    public function updateSocial(UpdateProfileSocialRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $profile = $user->profileSocial;
        if (!$profile) {
            $profile = new ProfileSocial(['user_id' => $user->id]);
        }
        $profile->fill($request->validated());
        $profile->save();
        return response()->json(['profileSocial' => $profile]);
    }

    /**
     * Создать социальный профиль пользователя
     */
    public function createSocial(UpdateProfileSocialRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (ProfileSocial::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => __('api.profile.social_exists')], 409);
        }
        $profile = new ProfileSocial(['user_id' => $user->id]);
        $profile->fill($request->validated());
        $profile->save();
        return response()->json(['profileSocial' => $profile], 201);
    }
}
