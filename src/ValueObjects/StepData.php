<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\ValueObjects;

use Carbon\Carbon;

readonly class StepData
{
    public function __construct(
        public string $stepId,
        public array $data,
        public bool $isValid,
        public array $errors,
        public Carbon $timestamp,
    ) {}

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    public function toArray(): array
    {
        return [
            'step_id' => $this->stepId,
            'data' => $this->data,
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
