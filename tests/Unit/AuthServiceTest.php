<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $result = $this->service->register([
            'name'     => 'Alice',
            'email'    => 'alice@example.com',
            'password' => 'password123',
        ]);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $this->service->register([
            'name'     => 'Bob',
            'email'    => 'bob@example.com',
            'password' => 'password123',
        ]);

        $result = $this->service->login([
            'email'    => 'bob@example.com',
            'password' => 'password123',
        ]);

        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('bob@example.com', $result['user']->email);
    }

    public function test_login_throws_with_wrong_password(): void
    {
        $this->service->register([
            'name'     => 'Carol',
            'email'    => 'carol@example.com',
            'password' => 'correctpassword',
        ]);

        $this->expectException(AuthenticationException::class);

        $this->service->login([
            'email'    => 'carol@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    public function test_login_throws_with_unknown_email(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->service->login([
            'email'    => 'nobody@example.com',
            'password' => 'anything',
        ]);
    }

    public function test_login_revokes_previous_tokens(): void
    {
        $registerResult = $this->service->register([
            'name'     => 'Dan',
            'email'    => 'dan@example.com',
            'password' => 'password123',
        ]);

        $firstToken = $registerResult['token'];

        $this->service->login([
            'email'    => 'dan@example.com',
            'password' => 'password123',
        ]);

        // After re-login the old token is gone
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}
