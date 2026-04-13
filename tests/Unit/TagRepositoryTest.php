<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Repositories\TagRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TagRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new TagRepository();
    }

    public function test_find_or_create_creates_new_tag(): void
    {
        $tag = $this->repo->findOrCreateByName('mobile');

        $this->assertDatabaseHas('tags', ['name' => 'mobile']);
        $this->assertEquals('mobile', $tag->name);
    }

    public function test_find_or_create_returns_existing_tag(): void
    {
        Tag::factory()->create(['name' => 'web']);

        $tag = $this->repo->findOrCreateByName('web');

        $this->assertDatabaseCount('tags', 1);
        $this->assertEquals('web', $tag->name);
    }

    public function test_find_or_create_normalises_to_lowercase(): void
    {
        $tag = $this->repo->findOrCreateByName('  WEB  ');

        $this->assertEquals('web', $tag->name);
    }

    public function test_resolve_ids_returns_array_of_ids(): void
    {
        $ids = $this->repo->resolveIds(['mobile', 'web', 'desktop']);

        $this->assertCount(3, $ids);
        $this->assertDatabaseCount('tags', 3);
    }

    public function test_find_by_name_returns_null_when_missing(): void
    {
        $this->assertNull($this->repo->findByName('nonexistent'));
    }

    public function test_all_returns_collection(): void
    {
        Tag::factory()->count(3)->create();

        $this->assertCount(3, $this->repo->all());
    }
}
