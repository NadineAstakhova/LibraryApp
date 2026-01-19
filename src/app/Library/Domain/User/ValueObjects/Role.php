<?php

namespace App\Library\Domain\User\ValueObjects;

/**
 * Value object representing user roles.
 */
enum Role: string
{
    case USER = 'user';
    case ADMIN = 'admin';

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isUser(): bool
    {
        return $this === self::USER;
    }

    /**
     * @param string $value
     * @return self
     * @throws \ValueError If invalid role value
     */
    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
