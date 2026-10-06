<?php

declare(strict_types=1);

use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Navigator;
use Invelity\WizardPackage\State;
use Invelity\WizardPackage\StepCollection;
use Invelity\WizardPackage\StepStatus;
use Invelity\WizardPackage\Tests\Fixtures\Steps\BillingStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\CalculatorStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\ConfirmationStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\NewsletterStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\PersonalDataStep;
use Invelity\WizardPackage\Tests\Fixtures\Steps\SummaryStep;

beforeEach(function () {
    $this->steps = new StepCollection([
        new CalculatorStep,
        new PersonalDataStep,
        new NewsletterStep,
        new BillingStep,
        new SummaryStep,
        new ConfirmationStep,
    ]);

    $this->navigator = fn (State $state = new State, bool $allowJumping = false): Navigator => new Navigator($this->steps, $state, $allowJumping);

    $this->step = fn (string $id): Step => $this->steps->findOrFail($id);

    $this->accessible = fn (Navigator $navigator): array => $this->steps
        ->filter(fn (Step $step): bool => $navigator->canAccess($step))
        ->ids();
});

it('starts at the first step and only opens it', function () {
    $navigator = ($this->navigator)();

    expect($navigator->current()?->id())->toBe('calculator')
        ->and(($this->accessible)($navigator))->toBe(['calculator']);
});

it('opens a step once every required step before it is finished', function () {
    $state = (new State)->withCompleted('calculator', [])->withCompleted('personal-data', []);

    expect(($this->accessible)(($this->navigator)($state)))
        ->toBe(['calculator', 'personal-data', 'newsletter', 'billing']);
});

it('lets the visitor pass optional steps without finishing them', function () {
    $state = (new State)
        ->withCompleted('calculator', [])
        ->withCompleted('personal-data', [])
        ->withCompleted('billing', []);

    expect(($this->accessible)(($this->navigator)($state)))
        ->toBe(['calculator', 'personal-data', 'newsletter', 'billing', 'summary']);
});

it('leaves conditionally skipped steps out of the flow', function () {
    $state = (new State)
        ->withMetadata(['plan' => 'free'])
        ->withCompleted('calculator', [])
        ->withCompleted('personal-data', []);

    $navigator = ($this->navigator)($state);

    expect($navigator->applicable()->ids())->toBe(['calculator', 'personal-data', 'newsletter', 'summary', 'confirmation'])
        ->and($navigator->canAccess(($this->step)('billing')))->toBeFalse()
        ->and($navigator->canAccess(($this->step)('summary')))->toBeTrue()
        ->and($navigator->next(($this->step)('newsletter'))?->id())->toBe('summary')
        ->and($navigator->previous(($this->step)('summary'))?->id())->toBe('newsletter');
});

it('does not let conditionally skipped steps block completion', function () {
    $state = (new State)
        ->withMetadata(['plan' => 'free'])
        ->withCompleted('calculator', [])
        ->withCompleted('personal-data', [])
        ->withCompleted('summary', []);

    $navigator = ($this->navigator)($state);

    expect($navigator->isComplete())->toBeTrue()
        ->and($navigator->missing()->ids())->toBe([]);
});

it('requires the dependencies of a step even when jumping is allowed', function () {
    $navigator = ($this->navigator)(new State, true);

    expect(($this->accessible)($navigator))
        ->toBe(['calculator', 'personal-data', 'newsletter', 'billing', 'confirmation'])
        ->and($navigator->canAccess(($this->step)('summary')))->toBeFalse();

    $state = (new State)->withCompleted('calculator', [])->withCompleted('personal-data', []);

    expect(($this->navigator)($state, true)->canAccess(($this->step)('summary')))->toBeTrue();
});

it('treats dependencies that left the flow as satisfied', function () {
    $steps = new StepCollection([
        new BillingStep,
        new class extends Invelity\WizardPackage\Step
        {
            protected string $id = 'invoice';

            protected array $dependencies = [BillingStep::class];
        },
    ]);

    $navigator = new Navigator($steps, (new State)->withMetadata(['plan' => 'free']), true);

    expect($navigator->canAccess($steps->findOrFail('invoice')))->toBeTrue();
});

it('opens display-only steps once the required steps before them are finished', function () {
    $state = (new State)
        ->withCompleted('calculator', [])
        ->withCompleted('personal-data', [])
        ->withCompleted('billing', [])
        ->withCompleted('summary', []);

    expect(($this->navigator)($state)->canAccess(($this->step)('confirmation')))->toBeTrue()
        ->and(($this->navigator)()->canAccess(($this->step)('confirmation')))->toBeFalse();
});

it('does not require display-only and optional steps for completion', function () {
    $state = (new State)
        ->withCompleted('calculator', [])
        ->withCompleted('personal-data', [])
        ->withCompleted('billing', []);

    $navigator = ($this->navigator)($state);

    expect($navigator->isComplete())->toBeFalse()
        ->and($navigator->missing()->ids())->toBe(['summary']);

    expect(($this->navigator)($state->withCompleted('summary', []))->isComplete())->toBeTrue();
});

it('finds the steps before and after a step within the flow', function () {
    $navigator = ($this->navigator)();

    expect($navigator->next(($this->step)('calculator'))?->id())->toBe('personal-data')
        ->and($navigator->next(($this->step)('summary'))?->id())->toBe('confirmation')
        ->and($navigator->next(($this->step)('confirmation')))->toBeNull()
        ->and($navigator->previous(($this->step)('personal-data'))?->id())->toBe('calculator')
        ->and($navigator->previous(($this->step)('calculator')))->toBeNull();
});

it('finds the first step that still needs input', function () {
    $state = (new State)->withCompleted('calculator', [])->withSkipped('newsletter');

    expect(($this->navigator)($state)->firstUnfinished()?->id())->toBe('personal-data');

    $done = $state->withCompleted('personal-data', [])->withCompleted('billing', [])->withCompleted('summary', []);

    expect(($this->navigator)($done)->firstUnfinished())->toBeNull();
});

it('uses the stored current step while it stays accessible', function () {
    $state = (new State)
        ->withCompleted('calculator', [])
        ->withCompleted('personal-data', [])
        ->withCurrent('calculator');

    expect(($this->navigator)($state)->current()?->id())->toBe('calculator');
});

it('falls back to the first unfinished step when the stored one is not accessible', function () {
    $state = (new State)->withCompleted('calculator', [])->withCurrent('summary');

    expect(($this->navigator)($state)->current()?->id())->toBe('personal-data');
});

it('falls back to the last step when everything is finished', function () {
    $state = (new State)
        ->withCompleted('calculator', [])
        ->withCompleted('personal-data', [])
        ->withSkipped('newsletter')
        ->withCompleted('billing', [])
        ->withCompleted('summary', []);

    expect(($this->navigator)($state)->current()?->id())->toBe('confirmation');
});

it('measures progress over the steps that take input', function () {
    $state = (new State)->withCompleted('calculator', [])->withSkipped('newsletter');

    $progress = ($this->navigator)($state)->progress();

    expect($progress->completed)->toBe(2)
        ->and($progress->total)->toBe(5)
        ->and($progress->percentage())->toBe(40);

    $free = ($this->navigator)($state->withMetadata(['plan' => 'free']))->progress();

    expect($free->total)->toBe(4)
        ->and($free->percentage())->toBe(50);
});

it('builds the navigation from the requested step', function () {
    $state = (new State)
        ->withCompleted('calculator', [])
        ->withCompleted('personal-data', [])
        ->withSkipped('newsletter')
        ->withCurrent('billing');

    $navigation = ($this->navigator)($state)->navigation(
        ($this->step)('personal-data'),
        fn (Step $step): string => "/order/{$step->id()}",
    );

    expect($navigation->current)->toBe('personal-data')
        ->and($navigation->previous)->toBe('calculator')
        ->and($navigation->next)->toBe('newsletter')
        ->and($navigation->canGoBack())->toBeTrue()
        ->and($navigation->canGoForward())->toBeTrue()
        ->and(array_map(fn ($item) => [$item->id, $item->position, $item->status, $item->current, $item->accessible, $item->url], $navigation->items))
        ->toBe([
            ['calculator', 1, StepStatus::Completed, false, true, '/order/calculator'],
            ['personal-data', 2, StepStatus::Completed, true, true, '/order/personal-data'],
            ['newsletter', 3, StepStatus::Skipped, false, true, '/order/newsletter'],
            ['billing', 4, StepStatus::Pending, false, true, '/order/billing'],
            ['summary', 5, StepStatus::Pending, false, false, '/order/summary'],
            ['confirmation', 6, StepStatus::Pending, false, false, '/order/confirmation'],
        ]);
});

it('builds the navigation from the current step by default', function () {
    $navigation = ($this->navigator)()->navigation();

    expect($navigation->current)->toBe('calculator')
        ->and($navigation->previous)->toBeNull()
        ->and($navigation->next)->toBe('personal-data')
        ->and($navigation->canGoBack())->toBeFalse()
        ->and($navigation->canGoForward())->toBeFalse()
        ->and($navigation->items[0]->url)->toBeNull()
        ->and($navigation->item('summary')?->optional)->toBeFalse()
        ->and($navigation->item('newsletter')?->optional)->toBeTrue()
        ->and($navigation->item('confirmation')?->displayOnly)->toBeTrue()
        ->and($navigation->item('missing'))->toBeNull();
});

it('serialises the navigation', function () {
    $array = ($this->navigator)()->navigation()->toArray();

    expect($array)->toHaveKeys(['current_step', 'previous_step', 'next_step', 'can_go_back', 'can_go_forward', 'items'])
        ->and($array['items'][0])->toBe([
            'id' => 'calculator',
            'title' => 'Calculator',
            'position' => 1,
            'status' => 'pending',
            'is_current' => true,
            'is_accessible' => true,
            'is_optional' => false,
            'is_display_only' => false,
            'url' => null,
        ])
        ->and(json_decode((string) json_encode(($this->navigator)()->navigation()), true))->toBe($array);
});
