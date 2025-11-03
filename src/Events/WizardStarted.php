<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WizardStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $wizardId,
        public readonly ?int $userId,
        public readonly string $sessionId,
        public readonly array $initialData,
    ) {}
}
