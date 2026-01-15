<?php

namespace App\DTO;

/**
 * DTO para actualizar la ubicación del usuario
 */
class UpdateLocationRequest
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            lat: (float)($data['lat'] ?? 0),
            lng: (float)($data['lng'] ?? 0)
        );
    }
}
