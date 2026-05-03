<?php

declare(strict_types=1);

namespace AdapterKit\Core;

final class Result
{
    private bool $success;
    private string $code;
    private string $message;
    private array $data;

    private function __construct(bool $success, string $code, string $message, array $data)
    {
        $this->success = $success;
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data;
    }

    public static function success(array $data = []): self
    {
        return new self(true, 'success', '', $data);
    }

    public static function failure(string $code, string $message, array $data = []): self
    {
        return new self(false, $code, $message, $data);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
