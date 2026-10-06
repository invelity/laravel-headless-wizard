<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

enum StepStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Skipped = 'skipped';
}
