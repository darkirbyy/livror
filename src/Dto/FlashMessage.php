<?php

declare(strict_types=1);

namespace App\Dto;

class FlashMessage
{
    public function __construct(private string $message, private array $params = [])
    {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }
}
