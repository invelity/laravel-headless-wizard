<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Facades;

use Illuminate\Support\Facades\Facade;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;

/**
 * @method static void initialize(string $wizardId, array $config = [])
 * @method static \WebSystem\WizardPackage\Contracts\WizardStepInterface|null getCurrentStep()
 * @method static \WebSystem\WizardPackage\Contracts\WizardStepInterface getStep(string $stepId)
 * @method static \WebSystem\WizardPackage\ValueObjects\StepResult processStep(string $stepId, array $data)
 * @method static void navigateToStep(string $stepId)
 * @method static \WebSystem\WizardPackage\Contracts\WizardStepInterface|null getNextStep()
 * @method static \WebSystem\WizardPackage\Contracts\WizardStepInterface|null getPreviousStep()
 * @method static bool canAccessStep(string $stepId)
 * @method static \WebSystem\WizardPackage\ValueObjects\WizardProgressValue getProgress()
 * @method static array getAllData()
 * @method static \WebSystem\WizardPackage\ValueObjects\StepResult complete()
 * @method static void reset()
 *
 * @see \WebSystem\WizardPackage\Contracts\WizardManagerInterface
 */
class Wizard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WizardManagerInterface::class;
    }
}
