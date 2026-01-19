<?php

namespace App\Library\UserInterface\Api\Controller\User;

use App\Http\Controllers\Controller;
use App\Library\Application\User\DTOs\LoginDTO;
use App\Library\Application\User\DTOs\RegisterUserDTO;
use App\Library\Application\User\DTOs\UpdatePasswordDTO;
use App\Library\Application\User\DTOs\UpdateProfileDTO;
use App\Library\Application\User\Exceptions\InvalidPasswordException;
use App\Library\Application\User\Exceptions\LoginException;
use App\Library\Application\User\Services\AuthService;
use App\Library\UserInterface\Api\Requests\User\LoginRequest;
use App\Library\UserInterface\Api\Requests\User\RegisterRequest;
use App\Library\UserInterface\Api\Requests\User\UpdatePasswordRequest;
use App\Library\UserInterface\Api\Requests\User\UpdateProfileRequest;
use App\Library\UserInterface\Base\ApiResponseJson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    #[OA\Post(
        path: "/api/v1/auth/register",
        summary: "Register a new user",
        description: "Create a new user account and return authentication token",
        operationId: "register",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", minLength: 8, example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User registered successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "user", ref: "#/components/schemas/User"),
                            new OA\Property(property: "token", type: "string"),
                            new OA\Property(property: "expires_in", type: "integer")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Bad request", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 429, description: "Too many requests")
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $userRegisterDto = new RegisterUserDTO(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
        );

        try {
            $result = $this->authService->register($userRegisterDto);

            return ApiResponseJson::successJsonResponse($result);
        } catch (\Exception $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/login",
        summary: "Login user",
        description: "Authenticate user and return JWT token",
        operationId: "login",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/AuthToken")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid credentials", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 429, description: "Too many requests")
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $userLoginDto = new LoginDTO(
            email: $request->input('email'),
            password: $request->input('password'),
        );

        try {
            $result = $this->authService->login($userLoginDto);

            return ApiResponseJson::successJsonResponse([
                'access_token' => $result['token'],
                'expires_in' => $result['expires_in'],
            ]);
        } catch (LoginException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/logout",
        summary: "Logout user",
        description: "Invalidate the current JWT token",
        operationId: "logout",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successfully logged out",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "message", type: "string", example: "Successfully logged out")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponseJson::successJsonResponse(['message' => 'Successfully logged out']);
    }

    #[OA\Post(
        path: "/api/v1/auth/refresh",
        summary: "Refresh JWT token",
        description: "Get a new JWT token using the current valid token",
        operationId: "refreshToken",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Token refreshed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/AuthToken")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Could not refresh token", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken();

            return ApiResponseJson::successJsonResponse([
                'access_token' => $result['token'],
                'expires_in' => $result['expires_in'],
            ]);
        } catch (\Exception $e) {
            return ApiResponseJson::errorJsonResponse(
                'Could not refresh token',
                Response::HTTP_UNAUTHORIZED
            );
        }
    }

    #[OA\Get(
        path: "/api/v1/auth/me",
        summary: "Get current user profile",
        description: "Get the authenticated user's profile information",
        operationId: "me",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "User profile retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/User")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "User not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function me(): JsonResponse
    {
        $profile = $this->authService->getCurrentUserProfile();

        if (!$profile) {
            return ApiResponseJson::errorJsonResponse(
                'User not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponseJson::successJsonResponse($profile);
    }

    #[OA\Put(
        path: "/api/v1/auth/password",
        summary: "Update password",
        description: "Update the authenticated user's password",
        operationId: "updatePassword",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["current_password", "new_password", "new_password_confirmation"],
                properties: [
                    new OA\Property(property: "current_password", type: "string", format: "password", example: "oldpassword123"),
                    new OA\Property(property: "new_password", type: "string", format: "password", minLength: 8, example: "newpassword123"),
                    new OA\Property(property: "new_password_confirmation", type: "string", format: "password", example: "newpassword123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "message", type: "string", example: "Password updated successfully")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Current password is incorrect", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "User not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return ApiResponseJson::errorJsonResponse(
                'User not found',
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $dto = new UpdatePasswordDTO(
                userId: $user->getId(),
                currentPassword: $request->input('current_password'),
                newPassword: $request->input('new_password'),
            );

            $this->authService->updatePassword($dto);

            return ApiResponseJson::successJsonResponse([
                'message' => 'Password updated successfully',
            ]);
        } catch (InvalidPasswordException $e) {
            return ApiResponseJson::errorJsonResponse(
                $e->getMessage(),
                Response::HTTP_UNAUTHORIZED
            );
        }
    }

    #[OA\Put(
        path: "/api/v1/auth/profile",
        summary: "Update profile",
        description: "Update the authenticated user's profile (name)",
        operationId: "updateProfile",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Updated")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Profile updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/User")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "User not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 500, description: "Failed to update profile", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return ApiResponseJson::errorJsonResponse(
                'User not found',
                Response::HTTP_NOT_FOUND
            );
        }

        $dto = new UpdateProfileDTO(
            userId: $user->getId(),
            name: $request->input('name'),
        );

        $updatedProfile = $this->authService->updateProfile($dto);

        if (!$updatedProfile) {
            return ApiResponseJson::errorJsonResponse(
                'Failed to update profile',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return ApiResponseJson::successJsonResponse($updatedProfile);
    }
}
