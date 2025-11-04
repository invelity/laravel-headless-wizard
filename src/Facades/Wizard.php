<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Facades;

use Illuminate\Support\Facades\Facade;
use Invelity\WizardPackage\Contracts\WizardManagerInterface;

/**
 * @method static void initialize(string $wizardId, array $config = [])
 * @method static \Invelity\WizardPackage\Contracts\WizardStepInterface|null getCurrentStep()
 * @method static \Invelity\WizardPackage\Contracts\WizardStepInterface getStep(string $stepId)
 * @method static \Invelity\WizardPackage\ValueObjects\StepResult processStep(string $stepId, array $data)
 * @method static void navigateToStep(string $stepId)
 * @method static \Invelity\WizardPackage\Contracts\WizardStepInterface|null getNextStep()
 * @method static \Invelity\WizardPackage\Contracts\WizardStepInterface|null getPreviousStep()
 * @method static bool canAccessStep(string $stepId)
 * @method static \Invelity\WizardPackage\ValueObjects\WizardProgressValue getProgress()
 * @method static array getAllData()
 * @method static \Invelity\WizardPackage\ValueObjects\StepResult complete()
 * @method static void reset()
 *
 * @see \Invelity\WizardPackage\Contracts\WizardManagerInterface
 */
class Wizard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WizardManagerInterface::class;
    }
}
