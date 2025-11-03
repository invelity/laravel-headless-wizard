<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\ValueObjects;

class StepResult
{
    public bool $isSuccess {
        get => $this->success;
    }

    public bool $hasErrors {
        get => count($this->errors) > 0;
    }

    private function __construct(
        public readonly bool $success,
        public readonly array $data,
        public readonly array $errors,
        public readonly ?string $message,
        public readonly ?string $redirectTo,
    ) {}

    public static function success(array $data = [], ?string $message = null): self
    {
        return new self(
            success: true,
            data: $data,
            errors: [],
            message: $message,
            redirectTo: null,
        );
    }

    public static function failure(array $errors, ?string $message = null): self
    {
        return new self(
            success: false,
            data: [],
            errors: $errors,
            message: $message,
            redirectTo: null,
        );
    }

    public static function redirect(string $url, array $data = []): self
    {
        return new self(
            success: true,
            data: $data,
            errors: [],
            message: null,
            redirectTo: $url,
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function shouldRedirect(): bool
    {
        return $this->redirectTo !== null;
    }
}
