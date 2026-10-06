<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * One step as shown in the wizard's navigation.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class NavigationItem implements Arrayable, JsonSerializable
{
    /**
     * Create a new navigation item.
     *
     * @param  int  $position  The position among the steps in the flow, starting at one.
     * @param  bool  $current  Whether this is the step the navigation was built for.
     */
    public function __construct(
        public string $id,
        public string $title,
        public int $position,
        public StepStatus $status,
        public bool $current,
        public bool $accessible,
        public bool $optional,
        public bool $displayOnly,
        public ?string $url = null,
    ) {}

    /**
     * @return array{id: string, title: string, position: int, status: string, is_current: bool, is_accessible: bool, is_optional: bool, is_display_only: bool, url: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'position' => $this->position,
            'status' => $this->status->value,
            'is_current' => $this->current,
            'is_accessible' => $this->accessible,
            'is_optional' => $this->optional,
            'is_display_only' => $this->displayOnly,
            'url' => $this->url,
        ];
    }

    /**
     * @return array{id: string, title: string, position: int, status: string, is_current: bool, is_accessible: bool, is_optional: bool, is_display_only: bool, url: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
