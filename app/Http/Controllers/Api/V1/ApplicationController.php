<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreApplicationRequest;
use App\Jobs\SendApplicationEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class ApplicationController extends Controller
{
    /**
     * Надіслати заявку на співпрацю (форма "Заявка" у футері сайту) — надсилає email на
     * адресу з config('services.application_email'), без збереження в БД.
     *
     * @OA\Post(
     *     path="/v1/applications",
     *     operationId="submitApplication",
     *     tags={"Applications"},
     *     summary="Надіслати заявку на співпрацю",
     *     security={{"apiKey": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"name", "email"},
     *
     *                 @OA\Property(property="name", type="string", example="Іван Іваненко"),
     *                 @OA\Property(property="email", type="string", format="email", example="ivan@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+380501234567"),
     *                 @OA\Property(property="about", type="string", example="Хочу долучитись як волонтер."),
     *                 @OA\Property(property="resume", type="string", format="binary", description="Резюме: PDF/DOC/DOCX, до 10MB")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Заявку надіслано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Заявку надіслано.")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
     */
    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $applicant = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->input('phone'),
            'about' => $request->input('about'),
        ];

        $resume = null;
        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            $path = $file->store('applications-tmp', 'local');
            $resume = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName() ?: Str::random(8).'.'.$file->getClientOriginalExtension(),
            ];
        }

        SendApplicationEmail::dispatch($applicant, $resume);

        return response()->json([
            'message' => 'Заявку надіслано.',
        ]);
    }
}
