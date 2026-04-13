<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Translation Management Service API',
    description: 'API for managing multi-locale translations with tag-based context.',
    contact: new OA\Contact(email: 'dev@example.com')
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Use the token returned from /api/auth/login'
)]
#[OA\Server(url: '/', description: 'Local server')]
abstract class Controller
{
}
