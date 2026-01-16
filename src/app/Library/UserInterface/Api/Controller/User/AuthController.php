<?php

namespace App\Library\UserInterface\Api\Controller\User;

use App\Http\Controllers\Controller;
use App\Library\Application\User\DTOs\LoginDTO;
use App\Library\Application\User\Exceptions\LoginException;
use App\Library\Application\User\Services\AuthService;
use App\Library\UserInterface\Api\Requests\User\LoginRequest;
use App\Library\UserInterface\Base\ApiResponseJson;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $userLoginDto = new LoginDTO(
            email: $request->input('email'),
            password: $request->input('password'),
        );

        try {
            $result = $this->authService->login($userLoginDto);

            return ApiResponseJson::successJsonResponse('Login successful', [
                'access_token' => $result['token'],
                'expires_in' => $result['expires_in'],
            ]);
        } catch (LoginException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), 401);
        }
    }

    /**
     * @throws \App\Library\Application\User\Exceptions\LoginException
     */
    public function logout(): JsonResponse
    {
        \Log::info('Logout attempt', [
            'user' => auth('api')->user(),
            'guard' => auth('api')->check() ? 'authenticated' : 'not authenticated'
        ]);

        try {
            $this->authService->logout();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}