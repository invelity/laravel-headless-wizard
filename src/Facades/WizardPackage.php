<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Facades;

use Illuminate\Support\Facades\Facade;
use WebSystem\WizardPackage\Contracts\WizardNavigationInterface;
use WebSystem\WizardPackage\Contracts\WizardStepInterface;
use WebSystem\WizardPackage\ValueObjects\StepResult;
use WebSystem\WizardPackage\ValueObjects\WizardProgressValue;

/**
 * @method static void initialize(string $wizardId, array $config = [])
 * @method static WizardStepInterface|null getCurrentStep()
 * @method static WizardStepInterface getStep(string $stepId)
 * @method static StepResult processStep(string $stepId, array $data)
 * @method static void navigateToStep(string $stepId)
 * @method static WizardStepInterface|null getNextStep()
 * @method static WizardStepInterface|null getPreviousStep()
 * @method static bool canAccessStep(string $stepId)
 * @method static WizardProgressValue getProgress()
 * @method static array getAllData()
 * @method static StepResult complete()
 * @method static void reset()
 * @method static void loadFromStorage(string $wizardId, int $instanceId)
 * @method static void deleteWizard(string $wizardId, int $instanceId)
 * @method static WizardNavigationInterface getNavigation()
 * @method static void skipStep(string $stepId)
 *
 * @see \WebSystem\WizardPackage\WizardPackage
 */
class WizardPackage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \WebSystem\WizardPackage\WizardPackage::class;
    }
}
