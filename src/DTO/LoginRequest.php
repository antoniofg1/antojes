<?php

namespace App\DTO;

/**
 * DTO para peticiones de login
 */
class LoginRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? '',
            password: $data['password'] ?? ''
        );
    }
}
