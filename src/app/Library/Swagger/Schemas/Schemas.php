<?php

namespace App\Library\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "User",
    type: "object",
    title: "User",
    description: "User model",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
        new OA\Property(property: "role", type: "string", enum: ["user", "admin"], example: "user"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-01-19T10:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-01-19T10:00:00Z")
    ]
)]
#[OA\Schema(
    schema: "Book",
    type: "object",
    title: "Book",
    description: "Book model",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", example: "The Great Gatsby"),
        new OA\Property(property: "author", type: "string", example: "F. Scott Fitzgerald"),
        new OA\Property(property: "isbn", type: "string", example: "978-0-7432-7356-5"),
        new OA\Property(property: "genre", type: "string", example: "Fiction"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "A novel about the American Dream"),
        new OA\Property(property: "total_copies", type: "integer", example: 5),
        new OA\Property(property: "available_copies", type: "integer", example: 3),
        new OA\Property(property: "version", type: "integer", example: 1),
        new OA\Property(property: "publication_year", type: "integer", nullable: true, example: 1925)
    ]
)]
#[OA\Schema(
    schema: "BookRental",
    type: "object",
    title: "BookRental",
    description: "Book rental model",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "book_id", type: "integer", example: 1),
        new OA\Property(property: "rented_at", type: "string", format: "date-time", example: "2026-01-19T10:00:00Z"),
        new OA\Property(property: "due_date", type: "string", format: "date-time", example: "2026-02-02T10:00:00Z"),
        new OA\Property(property: "returned_at", type: "string", format: "date-time", nullable: true, example: null),
        new OA\Property(property: "status", type: "string", enum: ["active", "completed", "overdue"], example: "active"),
        new OA\Property(property: "reading_progress", type: "integer", minimum: 0, maximum: 100, example: 25),
        new OA\Property(property: "extension_count", type: "integer", example: 0)
    ]
)]
#[OA\Schema(
    schema: "BookRentalWithBook",
    type: "object",
    title: "BookRentalWithBook",
    description: "Book rental with book details",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "book_id", type: "integer", example: 1),
        new OA\Property(property: "rented_at", type: "string", format: "date-time"),
        new OA\Property(property: "due_date", type: "string", format: "date-time"),
        new OA\Property(property: "returned_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["active", "completed", "overdue"]),
        new OA\Property(property: "reading_progress", type: "integer"),
        new OA\Property(property: "extension_count", type: "integer"),
        new OA\Property(property: "days_remaining", type: "integer", example: 14),
        new OA\Property(property: "can_extend", type: "boolean", example: true),
        new OA\Property(property: "is_overdue", type: "boolean", example: false),
        new OA\Property(property: "book", ref: "#/components/schemas/Book")
    ]
)]
#[OA\Schema(
    schema: "AuthToken",
    type: "object",
    title: "AuthToken",
    description: "Authentication token response",
    properties: [
        new OA\Property(property: "access_token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
        new OA\Property(property: "expires_in", type: "integer", example: 3600, description: "Token expiration time in seconds")
    ]
)]
#[OA\Schema(
    schema: "SuccessResponse",
    type: "object",
    title: "SuccessResponse",
    description: "Generic success response wrapper",
    properties: [
        new OA\Property(property: "data", type: "object")
    ]
)]
#[OA\Schema(
    schema: "ErrorResponse",
    type: "object",
    title: "ErrorResponse",
    description: "Error response",
    properties: [
        new OA\Property(property: "error", type: "string", example: "Error message")
    ]
)]
#[OA\Schema(
    schema: "PaginationMeta",
    type: "object",
    title: "PaginationMeta",
    description: "Pagination metadata",
    properties: [
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "last_page", type: "integer", example: 10),
        new OA\Property(property: "per_page", type: "integer", example: 15),
        new OA\Property(property: "total", type: "integer", example: 150)
    ]
)]
#[OA\Schema(
    schema: "PaginationLinks",
    type: "object",
    title: "PaginationLinks",
    description: "Pagination links",
    properties: [
        new OA\Property(property: "first", type: "string", example: "http://localhost:8000/api/v1/books?page=1"),
        new OA\Property(property: "last", type: "string", example: "http://localhost:8000/api/v1/books?page=10"),
        new OA\Property(property: "prev", type: "string", nullable: true, example: null),
        new OA\Property(property: "next", type: "string", nullable: true, example: "http://localhost:8000/api/v1/books?page=2")
    ]
)]
class Schemas
{
}
