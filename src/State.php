<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Throwable;

/**
 * The persisted state of one wizard for one visitor.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class State implements Arrayable
{
    /**
     * The version of the array layout written by toArray().
     */
    public const int VERSION = 2;

    /**
     * The keys of the array layout owned by this class; any other key is kept as an attribute.
     */
    private const array KEYS = [
        'version',
        'current_step',
        'completed_steps',
        'skipped_steps',
        'steps',
        'metadata',
        'started_at',
        'completed_at',
        'status',
    ];

    /**
     * Create a new state instance.
     *
     * @param  list<string>  $completed  The steps that were processed.
     * @param  list<string>  $skipped  The optional steps that were skipped.
     * @param  array<string, array<string, mixed>>  $data  The stored data, keyed by step.
     * @param  array<array-key, mixed>  $metadata
     * @param  array<string, mixed>  $attributes  Keys of the stored array that this package does not own.
     */
    public function __construct(
        public ?string $current = null,
        public array $completed = [],
        public array $skipped = [],
        public array $data = [],
        public array $metadata = [],
        public ?CarbonImmutable $startedAt = null,
        public ?CarbonImmutable $completedAt = null,
        public array $attributes = [],
    ) {}

    /**
     * Create a state from its array layout, including the layout written by 1.x.
     *
     * @param  array<array-key, mixed>  $state
     */
    public static function fromArray(array $state): self
    {
        $skipped = self::stepIds($state['skipped_steps'] ?? []);

        /** @var array<string, mixed> $attributes */
        $attributes = array_diff_key($state, array_flip(self::KEYS));

        $metadata = is_array($state['metadata'] ?? null) ? $state['metadata'] : [];

        return new self(
            current: is_string($state['current_step'] ?? null) ? $state['current_step'] : null,
            completed: array_values(array_diff(self::stepIds($state['completed_steps'] ?? []), $skipped)),
            skipped: $skipped,
            data: self::stepData($state['steps'] ?? []),
            metadata: $metadata,
            startedAt: self::date($state['started_at'] ?? null),
            completedAt: self::date($state['completed_at'] ?? null),
            attributes: $attributes,
        );
    }

    /**
     * Get the array layout of the state.
     *
     * Skipped steps are listed under "completed_steps" too, as 1.x expects.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->attributes,
            'version' => self::VERSION,
            'current_step' => $this->current,
            'completed_steps' => [...$this->completed, ...$this->skipped],
            'skipped_steps' => $this->skipped,
            'steps' => $this->data,
            'metadata' => $this->metadata,
            'started_at' => $this->startedAt?->toIso8601String(),
            'completed_at' => $this->completedAt?->toIso8601String(),
            'status' => $this->isCompleted() ? 'completed' : 'in_progress',
        ];
    }

    /**
     * Determine if the given step was processed.
     */
    public function hasCompleted(string $step): bool
    {
        return in_array($step, $this->completed, true);
    }

    /**
     * Determine if the given step was skipped.
     */
    public function hasSkipped(string $step): bool
    {
        return in_array($step, $this->skipped, true);
    }

    /**
     * Determine if the given step was either processed or skipped.
     */
    public function hasFinished(string $step): bool
    {
        return $this->hasCompleted($step) || $this->hasSkipped($step);
    }

    /**
     * Determine if the wizard has been started.
     */
    public function isStarted(): bool
    {
        return $this->startedAt !== null;
    }

    /**
     * Determine if the wizard has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    /**
     * Get the data stored for the given step.
     *
     * @return array<string, mixed>
     */
    public function data(string $step): array
    {
        return $this->data[$step] ?? [];
    }

    /**
     * Get a copy of the state positioned at the given step.
     */
    public function withCurrent(?string $step): self
    {
        return $this->copy(['current' => $step]);
    }

    /**
     * Get a copy of the state with the given step processed and its data stored.
     *
     * @param  array<string, mixed>  $data
     */
    public function withCompleted(string $step, array $data): self
    {
        return $this->copy([
            'completed' => $this->hasCompleted($step) ? $this->completed : [...$this->completed, $step],
            'skipped' => self::without($this->skipped, [$step]),
            'data' => [...$this->data, $step => $data],
        ]);
    }

    /**
     * Get a copy of the state with the given step skipped.
     */
    public function withSkipped(string $step): self
    {
        return $this->copy([
            'completed' => self::without($this->completed, [$step]),
            'skipped' => $this->hasSkipped($step) ? $this->skipped : [...$this->skipped, $step],
        ]);
    }

    /**
     * Get a copy of the state with the given steps neither processed nor skipped.
     *
     * Their data is kept for prefilling. A reopened wizard is no longer completed.
     */
    public function withReopened(string ...$steps): self
    {
        return $this->copy([
            'completed' => self::without($this->completed, $steps),
            'skipped' => self::without($this->skipped, $steps),
            'completedAt' => null,
        ]);
    }

    /**
     * Get a copy of the state with the given metadata.
     *
     * @param  array<array-key, mixed>  $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return $this->copy(['metadata' => $metadata]);
    }

    /**
     * Get a copy of the state started at the given time.
     */
    public function withStartedAt(DateTimeInterface $startedAt): self
    {
        return $this->copy(['startedAt' => CarbonImmutable::instance($startedAt)]);
    }

    /**
     * Get a copy of the state completed at the given time.
     */
    public function withCompletedAt(?DateTimeInterface $completedAt): self
    {
        return $this->copy(['completedAt' => $completedAt === null ? null : CarbonImmutable::instance($completedAt)]);
    }

    /**
     * Get a copy of the state with the given properties replaced.
     *
     * @param  array{current?: string|null, completed?: list<string>, skipped?: list<string>, data?: array<string, array<string, mixed>>, metadata?: array<array-key, mixed>, startedAt?: CarbonImmutable, completedAt?: CarbonImmutable|null}  $changes
     */
    private function copy(array $changes): self
    {
        return new self(
            current: array_key_exists('current', $changes) ? $changes['current'] : $this->current,
            completed: $changes['completed'] ?? $this->completed,
            skipped: $changes['skipped'] ?? $this->skipped,
            data: $changes['data'] ?? $this->data,
            metadata: $changes['metadata'] ?? $this->metadata,
            startedAt: $changes['startedAt'] ?? $this->startedAt,
            completedAt: array_key_exists('completedAt', $changes) ? $changes['completedAt'] : $this->completedAt,
            attributes: $this->attributes,
        );
    }

    /**
     * Remove the given step ids from a list.
     *
     * @param  list<string>  $steps
     * @param  array<array-key, string>  $remove
     * @return list<string>
     */
    private static function without(array $steps, array $remove): array
    {
        return array_values(array_diff($steps, $remove));
    }

    /**
     * Normalise a stored list of step ids.
     *
     * @return list<string>
     */
    private static function stepIds(mixed $steps): array
    {
        if (! is_array($steps)) {
            return [];
        }

        return array_values(array_unique(array_filter($steps, is_string(...))));
    }

    /**
     * Normalise the stored step data, dropping entries that hold no array.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function stepData(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        /** @var array<string, array<string, mixed>> */
        return array_filter($data, is_array(...));
    }

    /**
     * Parse a stored date.
     */
    private static function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
