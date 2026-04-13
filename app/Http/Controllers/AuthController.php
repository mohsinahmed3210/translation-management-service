<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Authentication endpoints')]
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    #[OA\Post(
        path: '/api/auth/register',
        operationId: 'authRegister',
        summary: 'Register a new user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'secret123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User registered'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    #[OA\Post(
        path: '/api/auth/login',
        operationId: 'authLogin',
        summary: 'Login and get an API token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        operationId: 'authLogout',
        summary: 'Revoke the current API token',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [new OA\Response(response: 200, description: 'Logged out')]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    #[OA\Get(
        path: '/api/auth/me',
        operationId: 'authMe',
        summary: 'Get the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [new OA\Response(response: 200, description: 'Current user')]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }
}
