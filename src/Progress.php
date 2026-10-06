<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * How far a visitor got through the steps that take input.
 *
 * @implements Arrayable<string, int>
 */
final readonly class Progress implements Arrayable, JsonSerializable
{
    /**
     * Create a new progress instance.
     *
     * @param  int  $completed  The steps that were processed or skipped.
     * @param  int  $total  The steps that take input and are part of the flow.
     */
    public function __construct(
        public int $completed,
        public int $total,
    ) {}

    /**
     * Get the completed share of the steps as a whole percentage.
     */
    public function percentage(): int
    {
        if ($this->total === 0) {
            return 100;
        }

        return (int) round(min($this->completed, $this->total) / $this->total * 100);
    }

    /**
     * Get the number of steps that are neither processed nor skipped.
     */
    public function remaining(): int
    {
        return max(0, $this->total - $this->completed);
    }

    /**
     * @return array{completed_steps: int, total_steps: int, percentage: int}
     */
    public function toArray(): array
    {
        return [
            'completed_steps' => $this->completed,
            'total_steps' => $this->total,
            'percentage' => $this->percentage(),
        ];
    }

    /**
     * @return array{completed_steps: int, total_steps: int, percentage: int}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
