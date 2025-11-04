<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StepCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $wizardId,
        public readonly string $stepId,
        public readonly array $stepData,
        public readonly int $progress,
    ) {}
}
