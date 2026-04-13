<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Export', description: 'Translation export for frontend applications')]
class ExportController extends Controller
{
    public function __construct(private readonly TranslationService $translationService)
    {
    }

    #[OA\Get(
        path: '/api/export/{locale}',
        operationId: 'exportLocale',
        summary: 'Export all translations for a locale as grouped JSON',
        description: 'Returns a grouped key-value map suitable for Vue.js i18n. Responses are cached in Redis and invalidated on any write operation.',
        security: [['sanctum' => []]],
        tags: ['Export'],
        parameters: [
            new OA\Parameter(name: 'locale', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'en')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translation export',
                content: new OA\JsonContent(example: ['general' => ['welcome.title' => 'Welcome', 'welcome.subtitle' => 'Hello world']])
            ),
        ]
    )]
    public function __invoke(string $locale): JsonResponse
    {
        $translations = $this->translationService->export($locale);

        return response()->json($translations);
    }
}
