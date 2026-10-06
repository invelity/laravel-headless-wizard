<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

use DateTimeInterface;

interface PrunableStore extends Store
{
    /**
     * Remove every state that has not changed since the given time.
     *
     * @return int The number of removed states.
     */
    public function prune(DateTimeInterface $before): int;
}
