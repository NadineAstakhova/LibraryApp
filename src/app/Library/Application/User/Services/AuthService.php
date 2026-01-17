<?php

namespace App\Library\Application\User\Services;

use App\Library\Application\User\DTOs\LoginDTO;
use App\Library\Application\User\Exceptions\LoginException;
use App\Library\Domain\User\Repositories\UserRepositoryInterface;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        //private UserRepositoryInterface $userRepository
    ) {}

    /**
     * @throws \App\Library\Application\User\Exceptions\LoginException
     */
    public function login(LoginDTO $dto): ?array
    {
        $credentials = [
            'email' => $dto->email,
            'password' => $dto->password,
        ];

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                throw new LoginException('Invalid credentials');
            }
        } catch (JWTException $e) {
            throw new LoginException('Could not create token');
        }

        return [
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    public function logout(): void
    {
        auth('api')->logout();
    }
}