<?php

namespace App\Library\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Library API",
    description: "Library Backend API for managing books, users, and book rentals",
    contact: new OA\Contact(
        email: "admin@library.com",
        name: "Library API Support"
    ),
    license: new OA\License(
        name: "MIT",
        url: "https://opensource.org/licenses/MIT"
    )
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Library API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter JWT token obtained from login endpoint"
)]
#[OA\Tag(
    name: "Authentication",
    description: "User authentication endpoints"
)]
#[OA\Tag(
    name: "Books",
    description: "Book management endpoints"
)]
#[OA\Tag(
    name: "Rentals",
    description: "Book rental management endpoints"
)]
class OpenApiInfo
{
}
