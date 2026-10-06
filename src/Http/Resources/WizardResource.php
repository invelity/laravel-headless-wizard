<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Invelity\WizardPackage\NavigationItem;
use Invelity\WizardPackage\Wizard;

/**
 * The single shape every wizard endpoint responds with.
 *
 * @property-read Wizard $resource
 */
final class WizardResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param  string|null  $step  The step to present the wizard from; the current step when null.
     */
    public function __construct(
        Wizard $wizard,
        private readonly ?string $step = null,
    ) {
        parent::__construct($wizard);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $wizard = $this->resource;
        $navigation = $wizard->navigation($this->step);
        $step = $navigation->current === null ? null : $wizard->step($navigation->current);

        return [
            'wizard' => $wizard->name(),
            'step' => $step === null ? null : [
                'id' => $step->id(),
                'title' => $step->title(),
                'is_optional' => $step->isOptional(),
                'is_display_only' => $step->isDisplayOnly(),
                'data' => (object) $wizard->data($step->id()),
            ],
            'previous_step' => $navigation->previous,
            'next_step' => $navigation->next,
            'is_completed' => $wizard->isCompleted(),
            'progress' => $wizard->progress()->toArray(),
            'navigation' => array_map(fn (NavigationItem $item): array => $item->toArray(), $navigation->items),
        ];
    }
}
