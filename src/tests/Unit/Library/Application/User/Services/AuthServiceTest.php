<?php

namespace Tests\Unit\Library\Application\User\Services;

use App\Library\Application\User\DTOs\LoginDTO;
use App\Library\Application\User\DTOs\RegisterUserDTO;
use App\Library\Application\User\DTOs\UpdatePasswordDTO;
use App\Library\Application\User\DTOs\UpdateProfileDTO;
use App\Library\Application\User\Exceptions\InvalidPasswordException;
use App\Library\Application\User\Exceptions\LoginException;
use App\Library\Application\User\Services\AuthService;
use App\Library\Application\User\Services\TokenServiceInterface;
use App\Library\Domain\User\Entities\User as UserEntity;
use App\Library\Domain\User\Repositories\UserRepositoryInterface;
use App\Library\Domain\User\ValueObjects\Role;
use App\Library\Infrastructure\User\Mappers\UserMapper;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private UserRepositoryInterface|MockInterface $userRepository;
    private UserMapper|MockInterface $userMapper;
    private TokenServiceInterface|MockInterface $tokenService;
    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->userMapper = Mockery::mock(UserMapper::class);
        $this->tokenService = Mockery::mock(TokenServiceInterface::class);

        $this->service = new AuthService(
            $this->userRepository,
            $this->userMapper,
            $this->tokenService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== register Tests ====================

    public function test_register_successfully(): void
    {
        // Arrange
        $dto = new RegisterUserDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123'
        );

        $userEntity = $this->createUserEntity(id: 1, name: 'John Doe', email: 'john@example.com');
        $userArray = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];
        $token = 'jwt-token-123';

        $this->userMapper
            ->shouldReceive('fromRegisterUserDTO')
            ->with($dto)
            ->once()
            ->andReturn($userEntity);

        $this->userRepository
            ->shouldReceive('create')
            ->with($userEntity)
            ->once()
            ->andReturn($userEntity);

        $this->tokenService
            ->shouldReceive('generateToken')
            ->with($userEntity)
            ->once()
            ->andReturn($token);

        $this->tokenService
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(60);

        $this->userMapper
            ->shouldReceive('toArray')
            ->with($userEntity)
            ->once()
            ->andReturn($userArray);

        // Act
        $result = $this->service->register($dto);

        // Assert
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals($userArray, $result['user']);
        $this->assertEquals($token, $result['token']);
        $this->assertEquals(3600, $result['expires_in']); // 60 * 60
    }

    // ==================== login Tests ====================

    public function test_login_successfully(): void
    {
        // Arrange
        $dto = new LoginDTO(
            email: 'john@example.com',
            password: 'password123'
        );

        $hashedPassword = Hash::make('password123');
        $userEntity = $this->createUserEntity(
            id: 1,
            email: 'john@example.com',
            passwordHash: $hashedPassword
        );
        $token = 'jwt-token-123';

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->once()
            ->andReturn($userEntity);

        $this->tokenService
            ->shouldReceive('generateToken')
            ->with($userEntity)
            ->once()
            ->andReturn($token);

        $this->tokenService
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(60);

        // Act
        $result = $this->service->login($dto);

        // Assert
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals($token, $result['token']);
        $this->assertEquals(3600, $result['expires_in']);
    }

    public function test_login_throws_exception_when_user_not_found(): void
    {
        // Arrange
        $dto = new LoginDTO(
            email: 'nonexistent@example.com',
            password: 'password123'
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('nonexistent@example.com')
            ->once()
            ->andReturn(null);

        // Assert
        $this->expectException(LoginException::class);
        $this->expectExceptionMessage('Invalid credentials');

        // Act
        $this->service->login($dto);
    }

    public function test_login_throws_exception_when_password_incorrect(): void
    {
        // Arrange
        $dto = new LoginDTO(
            email: 'john@example.com',
            password: 'wrongpassword'
        );

        $hashedPassword = Hash::make('correctpassword');
        $userEntity = $this->createUserEntity(
            id: 1,
            email: 'john@example.com',
            passwordHash: $hashedPassword
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->once()
            ->andReturn($userEntity);

        // Assert
        $this->expectException(LoginException::class);
        $this->expectExceptionMessage('Invalid credentials');

        // Act
        $this->service->login($dto);
    }

    // ==================== logout Tests ====================

    public function test_logout_successfully(): void
    {
        // Arrange
        $this->tokenService
            ->shouldReceive('logout')
            ->once();

        // Act
        $this->service->logout();

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    // ==================== refreshToken Tests ====================

    public function test_refresh_token_successfully(): void
    {
        // Arrange
        $newToken = 'new-jwt-token-456';

        $this->tokenService
            ->shouldReceive('refreshToken')
            ->once()
            ->andReturn($newToken);

        $this->tokenService
            ->shouldReceive('getTTL')
            ->once()
            ->andReturn(60);

        // Act
        $result = $this->service->refreshToken();

        // Assert
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals($newToken, $result['token']);
        $this->assertEquals(3600, $result['expires_in']);
    }

    // ==================== getAuthenticatedUser Tests ====================

    public function test_get_authenticated_user_returns_user(): void
    {
        // Arrange
        $userEntity = $this->createUserEntity(id: 1, name: 'John Doe');

        $this->tokenService
            ->shouldReceive('getAuthenticatedUser')
            ->once()
            ->andReturn($userEntity);

        // Act
        $result = $this->service->getAuthenticatedUser();

        // Assert
        $this->assertInstanceOf(UserEntity::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    public function test_get_authenticated_user_returns_null_when_not_authenticated(): void
    {
        // Arrange
        $this->tokenService
            ->shouldReceive('getAuthenticatedUser')
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->service->getAuthenticatedUser();

        // Assert
        $this->assertNull($result);
    }

    // ==================== getCurrentUserProfile Tests ====================

    public function test_get_current_user_profile_returns_user_array(): void
    {
        // Arrange
        $userEntity = $this->createUserEntity(id: 1, name: 'John Doe', email: 'john@example.com');
        $userArray = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];

        $this->tokenService
            ->shouldReceive('getAuthenticatedUser')
            ->once()
            ->andReturn($userEntity);

        $this->userMapper
            ->shouldReceive('toArray')
            ->with($userEntity)
            ->once()
            ->andReturn($userArray);

        // Act
        $result = $this->service->getCurrentUserProfile();

        // Assert
        $this->assertEquals($userArray, $result);
    }

    public function test_get_current_user_profile_returns_null_when_not_authenticated(): void
    {
        // Arrange
        $this->tokenService
            ->shouldReceive('getAuthenticatedUser')
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->service->getCurrentUserProfile();

        // Assert
        $this->assertNull($result);
    }

    // ==================== updatePassword Tests ====================

    public function test_update_password_successfully(): void
    {
        // Arrange
        $currentPassword = 'oldpassword123';
        $newPassword = 'newpassword456';
        $hashedCurrentPassword = Hash::make($currentPassword);

        $dto = new UpdatePasswordDTO(
            userId: 1,
            currentPassword: $currentPassword,
            newPassword: $newPassword
        );

        $userEntity = $this->createUserEntity(
            id: 1,
            passwordHash: $hashedCurrentPassword
        );

        $this->userRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($userEntity);

        $this->userRepository
            ->shouldReceive('updatePassword')
            ->with(1, Mockery::type('string'))
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->service->updatePassword($dto);

        // Assert
        $this->assertTrue($result);
    }

    public function test_update_password_returns_false_when_user_not_found(): void
    {
        // Arrange
        $dto = new UpdatePasswordDTO(
            userId: 999,
            currentPassword: 'oldpassword',
            newPassword: 'newpassword'
        );

        $this->userRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->service->updatePassword($dto);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_password_throws_exception_when_current_password_incorrect(): void
    {
        // Arrange
        $hashedPassword = Hash::make('correctpassword');

        $dto = new UpdatePasswordDTO(
            userId: 1,
            currentPassword: 'wrongpassword',
            newPassword: 'newpassword'
        );

        $userEntity = $this->createUserEntity(
            id: 1,
            passwordHash: $hashedPassword
        );

        $this->userRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($userEntity);

        // Assert
        $this->expectException(InvalidPasswordException::class);

        // Act
        $this->service->updatePassword($dto);
    }

    // ==================== updateProfile Tests ====================

    public function test_update_profile_successfully(): void
    {
        // Arrange
        $dto = new UpdateProfileDTO(
            userId: 1,
            name: 'Jane Doe'
        );

        $updatedUser = $this->createUserEntity(id: 1, name: 'Jane Doe');
        $userArray = ['id' => 1, 'name' => 'Jane Doe', 'email' => 'john@example.com'];

        $this->userRepository
            ->shouldReceive('updateProfile')
            ->with(1, 'Jane Doe')
            ->once()
            ->andReturn($updatedUser);

        $this->userMapper
            ->shouldReceive('toArray')
            ->with($updatedUser)
            ->once()
            ->andReturn($userArray);

        // Act
        $result = $this->service->updateProfile($dto);

        // Assert
        $this->assertEquals($userArray, $result);
        $this->assertEquals('Jane Doe', $result['name']);
    }

    public function test_update_profile_returns_current_user_when_no_updates(): void
    {
        // Arrange
        $dto = new UpdateProfileDTO(
            userId: 1,
            name: null // No updates
        );

        $userEntity = $this->createUserEntity(id: 1, name: 'John Doe');
        $userArray = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];

        $this->userRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($userEntity);

        $this->userMapper
            ->shouldReceive('toArray')
            ->with($userEntity)
            ->once()
            ->andReturn($userArray);

        // Act
        $result = $this->service->updateProfile($dto);

        // Assert
        $this->assertEquals($userArray, $result);
    }

    public function test_update_profile_returns_null_when_user_not_found(): void
    {
        // Arrange
        $dto = new UpdateProfileDTO(
            userId: 999,
            name: 'Jane Doe'
        );

        $this->userRepository
            ->shouldReceive('updateProfile')
            ->with(999, 'Jane Doe')
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->service->updateProfile($dto);

        // Assert
        $this->assertNull($result);
    }

    public function test_update_profile_returns_null_when_no_updates_and_user_not_found(): void
    {
        // Arrange
        $dto = new UpdateProfileDTO(
            userId: 999,
            name: null
        );

        $this->userRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->service->updateProfile($dto);

        // Assert
        $this->assertNull($result);
    }

    // ==================== Helper Methods ====================

    private function createUserEntity(
        ?int $id = 1,
        string $name = 'Test User',
        string $email = 'test@example.com',
        ?string $passwordHash = null,
        Role $role = Role::USER
    ): UserEntity {
        return new UserEntity(
            id: $id,
            name: $name,
            email: $email,
            passwordHash: $passwordHash ?? Hash::make('password123'),
            role: $role
        );
    }
}
