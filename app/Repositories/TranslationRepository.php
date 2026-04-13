<?php

namespace App\Repositories;

use App\Models\Translation;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TranslationRepository implements TranslationRepositoryInterface
{
    public function findById(int $id): ?Translation
    {
        return Translation::with('tags')->find($id);
    }

    public function findByLocaleAndKey(string $locale, string $key): ?Translation
    {
        return Translation::where('locale', $locale)->where('key', $key)->first();
    }

    public function create(array $data, array $tagIds = []): Translation
    {
        $translation = Translation::create($data);

        if (!empty($tagIds)) {
            $translation->tags()->sync($tagIds);
        }

        return $translation->load('tags');
    }

    public function update(Translation $translation, array $data, ?array $tagIds = null): Translation
    {
        $translation->update($data);

        if ($tagIds !== null) {
            $translation->tags()->sync($tagIds);
        }

        return $translation->load('tags');
    }

    public function delete(Translation $translation): bool
    {
        return (bool) $translation->delete();
    }

    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Translation::with('tags');

        if (!empty($filters['locale'])) {
            $query->forLocale($filters['locale']);
        }

        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : explode(',', $filters['tags']);
            $query->withTags(array_map('trim', $tags));
        }

        if (!empty($filters['key'])) {
            $query->where('key', 'like', '%' . $filters['key'] . '%');
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['group'])) {
            $query->where('group', $filters['group']);
        }

        return $query->orderBy('id')->paginate($perPage);
    }

    /**
     * Fetch all translations for a locale, structured as key => value.
     * Uses a raw query for maximum performance on large datasets.
     */
    public function exportByLocale(string $locale): array
    {
        $rows = DB::table('translations')
            ->where('locale', $locale)
            ->orderBy('key')
            ->select('key', 'value', 'group')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->group][$row->key] = $row->value;
        }

        return $result;
    }
}
