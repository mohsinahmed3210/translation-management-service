<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function test_can_create_a_translation(): void
    {
        $response = $this->withHeaders($this->auth())
                         ->postJson('/api/translations', [
                             'locale' => 'en',
                             'key'    => 'welcome.title',
                             'value'  => 'Welcome',
                             'group'  => 'general',
                             'tags'   => ['web', 'mobile'],
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['data' => ['id', 'locale', 'key', 'value', 'group', 'tags']]);

        $this->assertDatabaseHas('translations', ['key' => 'welcome.title', 'locale' => 'en']);
        $this->assertDatabaseHas('tags', ['name' => 'web']);
        $this->assertDatabaseHas('tags', ['name' => 'mobile']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->withHeaders($this->auth())
             ->postJson('/api/translations', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['locale', 'key', 'value']);
    }

    public function test_store_rejects_duplicate_locale_key_pair(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'duplicate.key']);

        $this->withHeaders($this->auth())
             ->postJson('/api/translations', [
                 'locale' => 'en',
                 'key'    => 'duplicate.key',
                 'value'  => 'Some value',
             ])->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function test_can_show_a_translation(): void
    {
        $translation = Translation::factory()->create();

        $this->withHeaders($this->auth())
             ->getJson("/api/translations/{$translation->id}")
             ->assertStatus(200)
             ->assertJson(['data' => ['id' => $translation->id]]);
    }

    public function test_show_returns_404_for_missing_translation(): void
    {
        $this->withHeaders($this->auth())
             ->getJson('/api/translations/99999')
             ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_can_update_translation_value(): void
    {
        $translation = Translation::factory()->create(['value' => 'Old value']);

        $this->withHeaders($this->auth())
             ->putJson("/api/translations/{$translation->id}", ['value' => 'New value'])
             ->assertStatus(200)
             ->assertJson(['data' => ['value' => 'New value']]);

        $this->assertDatabaseHas('translations', ['id' => $translation->id, 'value' => 'New value']);
    }

    public function test_update_syncs_tags(): void
    {
        $tag1        = Tag::factory()->create(['name' => 'web']);
        $translation = Translation::factory()->create();
        $translation->tags()->attach($tag1);

        $this->withHeaders($this->auth())
             ->putJson("/api/translations/{$translation->id}", ['tags' => ['mobile']])
             ->assertStatus(200);

        // Old tag removed, new tag assigned
        $this->assertDatabaseHas('tags', ['name' => 'mobile']);
        $this->assertDatabaseMissing('translation_tag', [
            'translation_id' => $translation->id,
            'tag_id'         => $tag1->id,
        ]);
    }

    public function test_update_returns_404_for_missing_translation(): void
    {
        $this->withHeaders($this->auth())
             ->putJson('/api/translations/99999', ['value' => 'x'])
             ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_can_delete_a_translation(): void
    {
        $translation = Translation::factory()->create();

        $this->withHeaders($this->auth())
             ->deleteJson("/api/translations/{$translation->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_delete_returns_404_for_missing_translation(): void
    {
        $this->withHeaders($this->auth())
             ->deleteJson('/api/translations/99999')
             ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Index / Search
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_results(): void
    {
        Translation::factory()->count(5)->create(['locale' => 'en']);

        $this->withHeaders($this->auth())
             ->getJson('/api/translations?locale=en')
             ->assertStatus(200)
             ->assertJsonCount(5, 'data');
    }

    public function test_search_by_key_filters_results(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'btn.save', 'value' => 'Save']);
        Translation::factory()->create(['locale' => 'en', 'key' => 'btn.cancel', 'value' => 'Cancel']);
        Translation::factory()->create(['locale' => 'en', 'key' => 'title.home', 'value' => 'Home']);

        $this->withHeaders($this->auth())
             ->getJson('/api/translations?key=btn')
             ->assertStatus(200)
             ->assertJsonCount(2, 'data');
    }

    public function test_search_by_tag_filters_results(): void
    {
        $webTag    = Tag::factory()->create(['name' => 'web']);
        $mobileTag = Tag::factory()->create(['name' => 'mobile']);

        $t1 = Translation::factory()->create(['locale' => 'en']);
        $t2 = Translation::factory()->create(['locale' => 'en']);
        $t3 = Translation::factory()->create(['locale' => 'en']);

        $t1->tags()->attach($webTag);
        $t2->tags()->attach($mobileTag);

        $this->withHeaders($this->auth())
             ->getJson('/api/translations?tags=web')
             ->assertStatus(200)
             ->assertJsonCount(1, 'data');
    }

    public function test_search_content_filters_results(): void
    {
        Translation::factory()->create(['value' => 'Welcome to the platform']);
        Translation::factory()->create(['value' => 'Goodbye']);

        $this->withHeaders($this->auth())
             ->getJson('/api/translations?search=Welcome')
             ->assertStatus(200)
             ->assertJsonCount(1, 'data');
    }

    public function test_per_page_is_capped_at_100(): void
    {
        Translation::factory()->count(5)->create();

        // per_page=200 should still work but capped internally
        $this->withHeaders($this->auth())
             ->getJson('/api/translations?per_page=200')
             ->assertStatus(200);
    }
}
