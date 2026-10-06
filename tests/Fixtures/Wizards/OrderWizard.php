<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Wizards;

use Invelity\WizardPackage\Tests\Fixtures\Steps\BillingStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\CalculatorStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\ConfirmationStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\NewsletterStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\PersonalDataStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\SummaryStep;
use Invelity\WizardPackage\Wizard;

class OrderWizard extends Wizard
{
    protected array $steps = [
        CalculatorStep::class,
        PersonalDataStep::class,
        NewsletterStep::class,
        BillingStep::class,
        SummaryStep::class,
        ConfirmationStep::class,
    ];
}
