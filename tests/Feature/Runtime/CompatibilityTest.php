<?php

declare(strict_types=1);

use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;

/**
 * A session record as 1.x writes it, with keys an application added itself.
 *
 * @return array<string, mixed>
 */
function legacySessionRecord(): array
{
    return [
        'wizard_id' => 'order',
        'current_step' => 'personal-data',
        'completed_steps' => ['calculator'],
        'steps' => ['calculator' => ['weight' => '2', 'price' => 9.0]],
        'metadata' => ['selected_carrier' => 'gls'],
        'started_at' => '2026-09-01T10:00:00+00:00',
        'packages' => [['weight' => 2]],
        'destination_country_code' => 'SK',
    ];
}

it('continues a wizard that 1.x stored in the session', function () {
    session()->put('wizard_order', legacySessionRecord());

    $wizard = Wizard::for(OrderWizard::class);

    expect($wizard->exists())->toBeTrue()
        ->and($wizard->current()?->id())->toBe('personal-data')
        ->and($wizard->data('calculator'))->toBe(['weight' => '2', 'price' => 9.0])
        ->and($wizard->metadata('selected_carrier'))->toBe('gls')
        ->and($wizard->state()->startedAt?->toIso8601String())->toBe('2026-09-01T10:00:00+00:00');

    $wizard->process('personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);

    $stored = session('wizard_order');

    expect($stored['packages'])->toBe([['weight' => 2]])
        ->and($stored['destination_country_code'])->toBe('SK')
        ->and($stored['wizard_id'])->toBe('order')
        ->and($stored['completed_steps'])->toBe(['calculator', 'personal-data'])
        ->and($stored['steps']['personal-data'])->toBe(['name' => 'Jane', 'email' => 'jane@example.com'])
        ->and($stored['current_step'])->toBe('newsletter')
        ->and($stored['version'])->toBe(2);
});

it('keeps the 1.x session key of a wizard that names itself like 1.x did', function () {
    session()->put('wizard_order-wizard', legacySessionRecord());

    $wizard = Wizard::for(new class extends OrderWizard
    {
        protected string $name = 'order-wizard';
    });

    expect($wizard->current()?->id())->toBe('personal-data');
});

it('treats skipped steps that 1.x listed as completed as finished', function () {
    session()->put('wizard_order', [...legacySessionRecord(), 'completed_steps' => ['calculator', 'personal-data', 'newsletter']]);

    expect(Wizard::for(OrderWizard::class)->canAccess('billing'))->toBeTrue();
});
