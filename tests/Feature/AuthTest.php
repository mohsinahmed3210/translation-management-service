<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    }

    public function test_register_validates_required_fields(): void
    {
        $this->postJson('/api/auth/register', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/auth/register', [
            'name'                  => 'Dup',
            'email'                 => 'dup@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'password123',
        ])->assertStatus(200)
          ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => 'ghost@example.com',
            'password' => 'password123',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
             ->postJson('/api/auth/logout')
             ->assertStatus(200);

        // Token should be revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_returns_current_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
             ->getJson('/api/auth/me')
             ->assertStatus(200)
             ->assertJson(['email' => $user->email]);
    }

    public function test_protected_routes_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/translations')->assertStatus(401);
        $this->getJson('/api/export/en')->assertStatus(401);
    }
}
