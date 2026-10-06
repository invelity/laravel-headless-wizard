<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Exceptions;

use LogicException;

/**
 * Thrown when a wizard or one of its steps is defined incorrectly.
 */
final class InvalidWizardException extends LogicException
{
    /**
     * Create an exception for a class that is not a wizard.
     */
    public static function notAWizard(string $class): self
    {
        return new self("[{$class}] is not a wizard; wizards extend [Invelity\\WizardPackage\\Wizard].");
    }

    /**
     * Create an exception for a step class that does not implement the step contract.
     */
    public static function notAStep(string $wizard, string $class): self
    {
        return new self("Step [{$class}] of wizard [{$wizard}] must implement [Invelity\\WizardPackage\\Contracts\\Step].");
    }

    /**
     * Create an exception for two steps with the same identifier.
     */
    public static function duplicateStep(string $wizard, string $step): self
    {
        return new self("Wizard [{$wizard}] has more than one step with the identifier [{$step}].");
    }

    /**
     * Create an exception for a dependency that is not a step of the wizard.
     */
    public static function unknownDependency(string $wizard, string $step, string $dependency): self
    {
        return new self("Step [{$step}] of wizard [{$wizard}] depends on [{$dependency}], which is not a step of the wizard.");
    }

    /**
     * Create an exception for a step whose handle() method returns something other than an array.
     */
    public static function invalidHandleResult(string $wizard, string $step): self
    {
        return new self("The handle() method of step [{$step}] of wizard [{$wizard}] must return an array or null.");
    }

    /**
     * Create an exception for a wizard used before the wizard manager prepared it.
     */
    public static function notResolved(string $wizard): self
    {
        return new self("Wizard [{$wizard}] was created directly; resolve it from the container or with Wizard::for().");
    }
}
