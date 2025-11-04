<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WizardCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $wizardId,
        public readonly array $allData,
        public readonly Carbon $completedAt,
    ) {}
}
