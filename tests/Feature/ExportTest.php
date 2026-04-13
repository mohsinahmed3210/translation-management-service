<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExportTest extends TestCase
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

    public function test_export_returns_grouped_translations(): void
    {
        Translation::factory()->create([
            'locale' => 'en',
            'key'    => 'welcome',
            'value'  => 'Hello',
            'group'  => 'general',
        ]);
        Translation::factory()->create([
            'locale' => 'en',
            'key'    => 'login_title',
            'value'  => 'Sign in',
            'group'  => 'auth',
        ]);

        $response = $this->withHeaders($this->auth())
                         ->getJson('/api/export/en');

        $response->assertStatus(200)
                 ->assertJson([
                     'general' => ['welcome' => 'Hello'],
                     'auth'    => ['login_title' => 'Sign in'],
                 ]);
    }

    public function test_export_returns_empty_array_for_unknown_locale(): void
    {
        $this->withHeaders($this->auth())
             ->getJson('/api/export/xx')
             ->assertStatus(200)
             ->assertJson([]);
    }

    public function test_export_only_returns_translations_for_requested_locale(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'en_key', 'value' => 'English', 'group' => 'g']);
        Translation::factory()->create(['locale' => 'fr', 'key' => 'fr_key', 'value' => 'French', 'group' => 'g']);

        $response = $this->withHeaders($this->auth())
                         ->getJson('/api/export/en');

        $data = $response->json();

        $this->assertArrayHasKey('en_key', $data['g']);
        $this->assertArrayNotHasKey('fr_key', $data['g'] ?? []);
    }

    public function test_export_reflects_updated_translations(): void
    {
        $translation = Translation::factory()->create([
            'locale' => 'en',
            'key'    => 'greet',
            'value'  => 'Hello',
            'group'  => 'general',
        ]);

        // First call caches the response
        $this->withHeaders($this->auth())->getJson('/api/export/en');

        // Update via the API
        $this->withHeaders($this->auth())
             ->putJson("/api/translations/{$translation->id}", ['value' => 'Hi there']);

        // Second call should return the fresh value (cache busted)
        $response = $this->withHeaders($this->auth())
                         ->getJson('/api/export/en');

        $response->assertJson(['general' => ['greet' => 'Hi there']]);
    }

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/export/en')->assertStatus(401);
    }

    public function test_export_response_time_is_acceptable(): void
    {
        Translation::factory()->count(500)->create(['locale' => 'en']);

        $start = microtime(true);

        $this->withHeaders($this->auth())
             ->getJson('/api/export/en')
             ->assertStatus(200);

        $elapsed = (microtime(true) - $start) * 1000;

        // Even without Redis in tests we expect < 2000ms locally
        $this->assertLessThan(2000, $elapsed, "Export took {$elapsed}ms, expected < 2000ms");
    }
}
