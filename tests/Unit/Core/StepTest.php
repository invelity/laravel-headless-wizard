<?php

declare(strict_types=1);

use Invelity\WizardPackage\State;
use Invelity\WizardPackage\Step;
use Invelity\WizardPackage\Tests\Fixtures\Steps\BillingStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\CalculatorStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\ConfirmationStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\NewsletterStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\PersonalDataStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\SummaryStep;

it('derives the id and title from the class name', function () {
    $step = new PersonalDataStep;

    expect($step->id())->toBe('personal-data')
        ->and($step->title())->toBe('Personal Data');
});

it('keeps class names without the Step suffix intact', function () {
    $step = new class extends Step
    {
        protected string $id = 'review';

        protected string $title = 'Review your order';
    };

    expect($step->id())->toBe('review')
        ->and($step->title())->toBe('Review your order');
});

it('is required, takes no input and has no dependencies by default', function () {
    $step = new BillingStep;

    expect($step->isOptional())->toBeFalse()
        ->and($step->isDisplayOnly())->toBeFalse()
        ->and($step->dependencies())->toBe([])
        ->and($step->formRequest())->toBeNull()
        ->and($step->shouldSkip(new State))->toBeFalse();
});

it('exposes its declared configuration', function () {
    expect((new NewsletterStep)->isOptional())->toBeTrue()
        ->and((new SummaryStep)->dependencies())->toBe([CalculatorStep::class, PersonalDataStep::class])
        ->and((new ConfirmationStep)->isDisplayOnly())->toBeTrue();
});

it('never validates input on a display-only step', function () {
    $step = new class extends Step
    {
        protected bool $displayOnly = true;

        protected ?string $formRequest = 'App\Http\Requests\IgnoredRequest';
    };

    expect($step->formRequest())->toBeNull();
});
