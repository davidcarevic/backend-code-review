<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Message;

class MessageDTO
{
    public string $uuid;
    public string $text;
    public ?string $status;

    public function __construct(string $uuid, string $text, ?string $status)
    {
        $this->uuid = $uuid;
        $this->text = $text;
        $this->status = $status;
    }

    public static function fromEntity(Message $message): self
    {
        return new self(
            (string) $message->getUuid(),  // Cast to string to prevent null errors
            (string) $message->getText(),  // Cast to string to prevent null errors
            $message->getStatus()
        );
    }
}
