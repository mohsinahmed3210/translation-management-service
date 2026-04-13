<?php

namespace App\Repositories\Contracts;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TranslationRepositoryInterface
{
    public function findById(int $id): ?Translation;

    public function create(array $data, array $tagIds = []): Translation;

    public function update(Translation $translation, array $data, ?array $tagIds = null): Translation;

    public function delete(Translation $translation): bool;

    public function search(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function exportByLocale(string $locale): array;

    public function findByLocaleAndKey(string $locale, string $key): ?Translation;
}
