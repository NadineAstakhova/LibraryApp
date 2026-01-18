<?php

namespace App\Library\UserInterface\Api\Controller\User;

use App\Http\Controllers\Controller;
use App\Library\Application\User\DTOs\LoginDTO;
use App\Library\Application\User\DTOs\RegisterUserDTO;
use App\Library\Application\User\Exceptions\LoginException;
use App\Library\Application\User\Services\AuthService;
use App\Library\UserInterface\Api\Requests\User\LoginRequest;
use App\Library\UserInterface\Api\Requests\User\RegisterRequest;
use App\Library\UserInterface\Base\ApiResponseJson;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

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
            return ApiResponseJson::errorJsonResponse($e->getMessage(), 400);
        }
    }

    /**
     * @param \App\Library\UserInterface\Api\Requests\User\LoginRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
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
            return ApiResponseJson::errorJsonResponse($e->getMessage(), 401);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponseJson::successJsonResponse(['Successfully logged out']);
    }
}