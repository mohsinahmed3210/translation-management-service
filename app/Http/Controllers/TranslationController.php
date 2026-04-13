<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Translations', description: 'Translation CRUD and search')]
class TranslationController extends Controller
{
    public function __construct(private readonly TranslationService $translationService)
    {
    }

    #[OA\Get(
        path: '/api/translations',
        operationId: 'translationIndex',
        summary: 'List / search translations',
        security: [['sanctum' => []]],
        tags: ['Translations'],
        parameters: [
            new OA\Parameter(name: 'locale', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'tags', in: 'query', description: 'Comma-separated tag names', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'key', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search in key and value', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'group', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated translation list')]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['locale', 'tags', 'key', 'search', 'group']);
        $perPage = (int) $request->get('per_page', 20);

        $results = $this->translationService->search($filters, min($perPage, 100));

        return TranslationResource::collection($results);
    }

    #[OA\Post(
        path: '/api/translations',
        operationId: 'translationStore',
        summary: 'Create a new translation',
        security: [['sanctum' => []]],
        tags: ['Translations'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['locale', 'key', 'value'],
                properties: [
                    new OA\Property(property: 'locale', type: 'string', example: 'en'),
                    new OA\Property(property: 'key', type: 'string', example: 'welcome.title'),
                    new OA\Property(property: 'value', type: 'string', example: 'Welcome'),
                    new OA\Property(property: 'group', type: 'string', example: 'general'),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), example: ['web', 'mobile']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Translation created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->translationService->create($request->validated());

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/translations/{id}',
        operationId: 'translationShow',
        summary: 'Get a single translation',
        security: [['sanctum' => []]],
        tags: ['Translations'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Translation found'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $translation = $this->translationService->find($id);

        if (!$translation) {
            return response()->json(['message' => 'Translation not found.'], 404);
        }

        return (new TranslationResource($translation))->response();
    }

    #[OA\Put(
        path: '/api/translations/{id}',
        operationId: 'translationUpdate',
        summary: 'Update a translation',
        security: [['sanctum' => []]],
        tags: ['Translations'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'locale', type: 'string'),
                    new OA\Property(property: 'key', type: 'string'),
                    new OA\Property(property: 'value', type: 'string'),
                    new OA\Property(property: 'group', type: 'string'),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Translation updated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function update(UpdateTranslationRequest $request, int $id): JsonResponse
    {
        $translation = $this->translationService->find($id);

        if (!$translation) {
            return response()->json(['message' => 'Translation not found.'], 404);
        }

        $translation = $this->translationService->update($translation, $request->validated());

        return (new TranslationResource($translation))->response();
    }

    #[OA\Delete(
        path: '/api/translations/{id}',
        operationId: 'translationDestroy',
        summary: 'Delete a translation',
        security: [['sanctum' => []]],
        tags: ['Translations'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $translation = $this->translationService->find($id);

        if (!$translation) {
            return response()->json(['message' => 'Translation not found.'], 404);
        }

        $this->translationService->delete($translation);

        return response()->json(['message' => 'Translation deleted.']);
    }
}
