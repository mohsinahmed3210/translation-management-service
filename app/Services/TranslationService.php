<?php

namespace App\Services;

use App\Models\Translation;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    // Cache TTL: 1 hour. We bust the cache on any write.
    private const EXPORT_CACHE_TTL = 3600;
    private const EXPORT_CACHE_KEY = 'translations_export_';

    public function __construct(
        private readonly TranslationRepositoryInterface $translationRepository,
        private readonly TagRepositoryInterface $tagRepository,
    ) {
    }

    public function find(int $id): ?Translation
    {
        return $this->translationRepository->findById($id);
    }

    public function create(array $data): Translation
    {
        $tagIds = $this->resolveTagIds($data['tags'] ?? []);
        unset($data['tags']);

        $translation = $this->translationRepository->create($data, $tagIds);

        $this->bustExportCache($translation->locale);

        return $translation;
    }

    public function update(Translation $translation, array $data): Translation
    {
        $tagIds = isset($data['tags']) ? $this->resolveTagIds($data['tags']) : null;
        unset($data['tags']);

        $previousLocale = $translation->locale;
        $translation = $this->translationRepository->update($translation, $data, $tagIds);

        $this->bustExportCache($previousLocale);

        // If locale changed, bust the new one too
        if (isset($data['locale']) && $data['locale'] !== $previousLocale) {
            $this->bustExportCache($data['locale']);
        }

        return $translation;
    }

    public function delete(Translation $translation): bool
    {
        $locale = $translation->locale;
        $deleted = $this->translationRepository->delete($translation);

        if ($deleted) {
            $this->bustExportCache($locale);
        }

        return $deleted;
    }

    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->translationRepository->search($filters, $perPage);
    }

    /**
     * Export all translations for a locale as a nested array.
     * Results are cached but always invalidated on writes, so callers
     * always get fresh data without a full DB hit every request.
     */
    public function export(string $locale): array
    {
        $cacheKey = self::EXPORT_CACHE_KEY . $locale;

        return Cache::remember($cacheKey, self::EXPORT_CACHE_TTL, function () use ($locale) {
            return $this->translationRepository->exportByLocale($locale);
        });
    }

    private function resolveTagIds(array $tagNames): array
    {
        if (empty($tagNames)) {
            return [];
        }

        return $this->tagRepository->resolveIds($tagNames);
    }

    private function bustExportCache(string $locale): void
    {
        Cache::forget(self::EXPORT_CACHE_KEY . $locale);
    }
}
