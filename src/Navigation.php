<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * The navigation of a wizard as seen from one of its steps.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Navigation implements Arrayable, JsonSerializable
{
    /**
     * Create a new navigation instance.
     *
     * @param  list<NavigationItem>  $items  The steps in the flow, in order.
     * @param  string|null  $current  The step the navigation was built for.
     */
    public function __construct(
        public array $items,
        public ?string $current,
        public ?string $previous,
        public ?string $next,
    ) {}

    /**
     * Get the item of the given step.
     */
    public function item(string $step): ?NavigationItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $step) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Determine if there is a step before the current one.
     */
    public function canGoBack(): bool
    {
        return $this->previous !== null;
    }

    /**
     * Determine if the step after the current one is accessible.
     */
    public function canGoForward(): bool
    {
        return $this->next !== null && $this->item($this->next)?->accessible === true;
    }

    /**
     * @return array{current_step: string|null, previous_step: string|null, next_step: string|null, can_go_back: bool, can_go_forward: bool, items: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'current_step' => $this->current,
            'previous_step' => $this->previous,
            'next_step' => $this->next,
            'can_go_back' => $this->canGoBack(),
            'can_go_forward' => $this->canGoForward(),
            'items' => array_map(fn (NavigationItem $item): array => $item->toArray(), $this->items),
        ];
    }

    /**
     * @return array{current_step: string|null, previous_step: string|null, next_step: string|null, can_go_back: bool, can_go_forward: bool, items: list<array<string, mixed>>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
