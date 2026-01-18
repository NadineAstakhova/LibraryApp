<?php

namespace App\Library\Application\User\Services;

use App\Library\Application\User\DTOs\LoginDTO;
use App\Library\Application\User\DTOs\RegisterUserDTO;
use App\Library\Application\User\Exceptions\LoginException;
use App\Library\Domain\User\Repositories\UserRepositoryInterface;
use App\Library\Infrastructure\User\Mappers\UserMapper;
use Illuminate\Support\Facades\Hash;

readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserMapper $userMapper,
        private JWTService $jwtService
    ) {}

    public function register(RegisterUserDTO $dto): array
    {
        $userEntity = $this->userMapper->fromRegisterUserDTO($dto);;

        $user = $this->userRepository->create($userEntity);

        $token = $this->jwtService->generateToken($this->userMapper->toModel($user));

        return [
            'user' => $this->userMapper->toArray($user),
            'token' => $token,
            'expires_in' => $this->jwtService->getTTL() * 60,
        ];
    }

    /**
     * @throws \App\Library\Application\User\Exceptions\LoginException
     */
    public function login(LoginDTO $dto): ?array
    {
        $userEntity = $this->userRepository->findByEmail($dto->email);

        if (!$userEntity) {
            throw new LoginException('Invalid credentials');
        }

        if (!Hash::check($dto->password, $userEntity->getPasswordHash())) {
            throw new LoginException('Invalid credentials');
        }

        $token = $this->jwtService->generateToken($this->userMapper->toModel($userEntity));

        return [
            'token' => $token,
            'expires_in' => $this->jwtService->getTTL() * 60,
        ];
    }

    public function logout(): void
    {
        $this->jwtService->logout();
    }
}