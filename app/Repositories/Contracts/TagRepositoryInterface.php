<?php

namespace App\Repositories\Contracts;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

interface TagRepositoryInterface
{
    public function all(): Collection;

    public function findByName(string $name): ?Tag;

    public function findOrCreateByName(string $name): Tag;

    public function resolveIds(array $names): array;
}
