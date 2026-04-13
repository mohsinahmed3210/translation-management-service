<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TagRepository implements TagRepositoryInterface
{
    public function all(): Collection
    {
        return Tag::all();
    }

    public function findByName(string $name): ?Tag
    {
        return Tag::where('name', $name)->first();
    }

    public function findOrCreateByName(string $name): Tag
    {
        return Tag::firstOrCreate(['name' => strtolower(trim($name))]);
    }

    /**
     * Resolve tag names to their IDs, creating missing tags on the fly.
     */
    public function resolveIds(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            $tag = $this->findOrCreateByName($name);
            $ids[] = $tag->id;
        }

        return $ids;
    }
}
