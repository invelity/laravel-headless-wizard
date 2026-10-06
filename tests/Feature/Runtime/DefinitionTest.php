<?php

declare(strict_types=1);

use Invelity\WizardPackage\Exceptions\InvalidWizardException;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Step;
use Invelity\WizardPackage\Tests\Fixtures\Steps\CalculatorStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\SummaryStep;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;
use Invelity\WizardPackage\Wizard as BaseWizard;

/**
 * Create a wizard with the given step classes.
 *
 * @param  list<class-string>  $steps
 */
function wizardWithSteps(array $steps): BaseWizard
{
    return Wizard::for(new class($steps) extends BaseWizard
    {
        protected string $name = 'defined';

        /**
         * @param  list<class-string>  $steps
         */
        public function __construct(array $steps)
        {
            /** @var list<class-string<Invelity\WizardPackage\Contracts\Step>> $steps */
            $this->steps = $steps;
        }
    });
}

it('derives its name from the class name', function () {
    expect(Wizard::for(OrderWizard::class)->name())->toBe('order');
});

it('lists its steps in order', function () {
    expect(Wizard::for(OrderWizard::class)->steps()->ids())
        ->toBe(['calculator', 'personal-data', 'newsletter', 'billing', 'summary', 'confirmation']);
});

it('resolves its steps through the container', function () {
    $calculator = new CalculatorStep;
    app()->instance(CalculatorStep::class, $calculator);

    expect(Wizard::for(OrderWizard::class)->step('calculator'))->toBe($calculator);
});

it('rejects step classes that are not steps', function () {
    wizardWithSteps([stdClass::class])->steps();
})->throws(InvalidWizardException::class, 'Step [stdClass] of wizard');

it('rejects two steps with the same identifier', function () {
    $duplicate = new class extends Step
    {
        protected string $id = 'calculator';
    };

    wizardWithSteps([CalculatorStep::class, $duplicate::class])->steps();
})->throws(InvalidWizardException::class, 'has more than one step with the identifier [calculator]');

it('rejects dependencies on steps outside the wizard', function () {
    wizardWithSteps([SummaryStep::class])->steps();
})->throws(InvalidWizardException::class, 'depends on ['.CalculatorStep::class.'], which is not a step of the wizard');

it('rejects handle() methods that return something other than an array', function () {
    $step = new class extends Step
    {
        protected string $id = 'broken';

        public function handle(): string
        {
            return 'nope';
        }
    };

    wizardWithSteps([$step::class])->process('broken');
})->throws(InvalidWizardException::class, 'must return an array or null');

it('stores the validated data when handle() returns nothing', function () {
    $step = new class extends Step
    {
        protected string $id = 'side-effect';

        public static int $calls = 0;

        public function handle(): void
        {
            self::$calls++;
        }
    };

    expect(wizardWithSteps([$step::class])->process('side-effect'))->toBe([])
        ->and($step::$calls)->toBe(1);
});
