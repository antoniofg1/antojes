<?php

namespace App\DTO;

/**
 * DTO para enviar mensajes
 */
class SendMessageRequest
{
    public function __construct(
        public readonly int $chatId,
        public readonly string $text
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            chatId: (int)($data['chat_id'] ?? 0),
            text: $data['text'] ?? ''
        );
    }
}
