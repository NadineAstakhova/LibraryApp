<?php

namespace App\Library\Application\User\Services;

use App\Library\Application\User\DTOs\LoginDTO;
use App\Library\Application\User\DTOs\RegisterUserDTO;
use App\Library\Application\User\DTOs\UpdatePasswordDTO;
use App\Library\Application\User\DTOs\UpdateProfileDTO;
use App\Library\Application\User\Exceptions\InvalidPasswordException;
use App\Library\Application\User\Exceptions\LoginException;
use App\Library\Domain\User\Entities\User as UserEntity;
use App\Library\Domain\User\Repositories\UserRepositoryInterface;
use App\Library\Infrastructure\User\Mappers\UserMapper;
use Illuminate\Support\Facades\Hash;

readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserMapper $userMapper,
        private TokenServiceInterface $tokenService
    ) {}

    /**
     * @param \App\Library\Application\User\DTOs\RegisterUserDTO $dto
     *
     * @return array
     */
    public function register(RegisterUserDTO $dto): array
    {
        $userEntity = $this->userMapper->fromRegisterUserDTO($dto);

        $user = $this->userRepository->create($userEntity);

        $token = $this->tokenService->generateToken($user);

        return [
            'user' => $this->userMapper->toArray($user),
            'token' => $token,
            'expires_in' => $this->tokenService->getTTL() * 60,
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

        $token = $this->tokenService->generateToken($userEntity);

        return [
            'token' => $token,
            'expires_in' => $this->tokenService->getTTL() * 60,
        ];
    }

    /**
     * @return void
     */
    public function logout(): void
    {
        $this->tokenService->logout();
    }

    /**
     * @return array Token data with new token and expiration
     */
    public function refreshToken(): array
    {
        $newToken = $this->tokenService->refreshToken();

        return [
            'token' => $newToken,
            'expires_in' => $this->tokenService->getTTL() * 60,
        ];
    }

    /**
     * @return UserEntity|null
     */
    public function getAuthenticatedUser(): ?UserEntity
    {
        return $this->tokenService->getAuthenticatedUser();
    }

    /**
     * @return array|null User data or null if not authenticated
     */
    public function getCurrentUserProfile(): ?array
    {
        $user = $this->tokenService->getAuthenticatedUser();

        if (!$user) {
            return null;
        }

        return $this->userMapper->toArray($user);
    }

    /**
     * @param UpdatePasswordDTO $dto Password update data
     * @return bool True if password was updated
     * @throws InvalidPasswordException If current password is incorrect
     */
    public function updatePassword(UpdatePasswordDTO $dto): bool
    {
        $user = $this->userRepository->findById($dto->userId);

        if (!$user) {
            return false;
        }

        if (!Hash::check($dto->currentPassword, $user->getPasswordHash())) {
            throw new InvalidPasswordException();
        }

        $hashedPassword = Hash::make($dto->newPassword);

        return $this->userRepository->updatePassword($dto->userId, $hashedPassword);
    }

    /**
     * @param UpdateProfileDTO $dto Profile update data
     * @return array|null Updated user data or null if not found
     */
    public function updateProfile(UpdateProfileDTO $dto): ?array
    {
        if (!$dto->hasUpdates()) {
            $user = $this->userRepository->findById($dto->userId);
            return $user ? $this->userMapper->toArray($user) : null;
        }

        $updatedUser = $this->userRepository->updateProfile($dto->userId, $dto->name);

        return $updatedUser ? $this->userMapper->toArray($updatedUser) : null;
    }
}