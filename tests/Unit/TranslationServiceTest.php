<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    private TranslationService $service;
    private MockInterface $translationRepo;
    private MockInterface $tagRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translationRepo = Mockery::mock(TranslationRepositoryInterface::class);
        $this->tagRepo         = Mockery::mock(TagRepositoryInterface::class);

        $this->service = new TranslationService($this->translationRepo, $this->tagRepo);
    }

    public function test_find_delegates_to_repository(): void
    {
        $translation = new Translation(['id' => 1]);

        $this->translationRepo
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($translation);

        $result = $this->service->find(1);

        $this->assertSame($translation, $result);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        $this->translationRepo
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->assertNull($this->service->find(999));
    }

    public function test_create_resolves_tag_ids_and_busts_cache(): void
    {
        Cache::shouldReceive('forget')->once()->with('translations_export_en');

        $this->tagRepo
            ->shouldReceive('resolveIds')
            ->once()
            ->with(['web', 'mobile'])
            ->andReturn([1, 2]);

        $translation = new Translation(['locale' => 'en', 'key' => 'foo', 'value' => 'bar']);

        $this->translationRepo
            ->shouldReceive('create')
            ->once()
            ->with(
                ['locale' => 'en', 'key' => 'foo', 'value' => 'bar'],
                [1, 2]
            )
            ->andReturn($translation);

        $result = $this->service->create([
            'locale' => 'en',
            'key'    => 'foo',
            'value'  => 'bar',
            'tags'   => ['web', 'mobile'],
        ]);

        $this->assertSame($translation, $result);
    }

    public function test_create_without_tags_passes_empty_array(): void
    {
        Cache::shouldReceive('forget')->once();

        $this->tagRepo->shouldNotReceive('resolveIds');

        $translation = new Translation(['locale' => 'en', 'key' => 'k', 'value' => 'v']);

        $this->translationRepo
            ->shouldReceive('create')
            ->once()
            ->with(['locale' => 'en', 'key' => 'k', 'value' => 'v'], [])
            ->andReturn($translation);

        $result = $this->service->create(['locale' => 'en', 'key' => 'k', 'value' => 'v']);

        $this->assertSame($translation, $result);
    }

    public function test_update_busts_cache_for_original_locale(): void
    {
        Cache::shouldReceive('forget')->once()->with('translations_export_fr');

        $translation = new Translation(['locale' => 'fr', 'key' => 'k', 'value' => 'old']);

        $updated = new Translation(['locale' => 'fr', 'key' => 'k', 'value' => 'new']);

        $this->translationRepo
            ->shouldReceive('update')
            ->once()
            ->with($translation, ['value' => 'new'], null)
            ->andReturn($updated);

        $result = $this->service->update($translation, ['value' => 'new']);

        $this->assertSame($updated, $result);
    }

    public function test_update_busts_both_locales_when_locale_changes(): void
    {
        Cache::shouldReceive('forget')->with('translations_export_fr')->once();
        Cache::shouldReceive('forget')->with('translations_export_es')->once();

        $translation = new Translation(['locale' => 'fr', 'key' => 'k', 'value' => 'v']);
        $updated     = new Translation(['locale' => 'es', 'key' => 'k', 'value' => 'v']);

        $this->translationRepo
            ->shouldReceive('update')
            ->once()
            ->andReturn($updated);

        $result = $this->service->update($translation, ['locale' => 'es', 'value' => 'v']);

        $this->assertSame($updated, $result);
    }

    public function test_delete_busts_cache_on_success(): void
    {
        Cache::shouldReceive('forget')->once()->with('translations_export_en');

        $translation = new Translation(['locale' => 'en']);

        $this->translationRepo
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->service->delete($translation));
    }

    public function test_export_caches_results(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('translations_export_en', 3600, Mockery::type(\Closure::class))
            ->andReturn(['general' => ['key' => 'value']]);

        $result = $this->service->export('en');

        $this->assertEquals(['general' => ['key' => 'value']], $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
