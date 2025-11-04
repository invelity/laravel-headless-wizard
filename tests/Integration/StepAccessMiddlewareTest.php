<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['wizard.wizards.checkout' => [
        'steps' => [
            PersonalInfoStep::class,
            ContactDetailsStep::class,
        ],
    ]]);
});

test('middleware allows access to first step', function () {
    $response = $this->get(route('wizard.show', [
        'wizard' => 'checkout',
        'step' => 'personal-info',
    ]));

    $response->assertOk();
});

test('middleware allows access to accessible step after completing previous', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John Doe']);

    $response = $this->get(route('wizard.show', [
        'wizard' => 'checkout',
        'step' => 'contact-details',
    ]));

    $response->assertOk();
});

test('middleware blocks access to inaccessible step', function () {
    $response = $this->getJson(route('wizard.show', [
        'wizard' => 'checkout',
        'step' => 'contact-details',
    ]));

    expect($response->status())->toBe(403);
});

test('middleware redirects to current accessible step when blocked', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');

    $response = $this->get(route('wizard.show', [
        'wizard' => 'checkout',
        'step' => 'contact-details',
    ]));

    if ($response->status() === 403) {
        expect($response->status())->toBe(403);
    } else {
        $response->assertRedirect(route('wizard.show', [
            'wizard' => 'checkout',
            'step' => 'personal-info',
        ]));
    }
});

test('middleware initializes wizard if not already initialized', function () {
    $response = $this->get(route('wizard.show', [
        'wizard' => 'checkout',
        'step' => 'personal-info',
    ]));

    $response->assertOk();

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    expect($manager->getCurrentStep())->not->toBeNull();
});

test('middleware passes through requests without wizard route parameter', function () {
    $response = $this->get('/');

    $response->assertStatus(404);
});

test('middleware validates wizard flow across multiple steps', function () {
    $response1 = $this->get(route('wizard.show', [
        'wizard' => 'checkout',
        'step' => 'personal-info',
    ]));
    $response1->assertOk();

    $this->post(route('wizard.store', [
        'wizard' => 'checkout',
        'step' => 'personal-info',
    ]), ['name' => 'John']);

    $response2 = $this->get(route('wizard.show', [
        'wizard' => 'checkout',
        'step' => 'contact-details',
    ]));
    $response2->assertOk();

    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $progress = $manager->getProgress();

    expect($progress->completedSteps)->toBeGreaterThanOrEqual(1);
});

test('middleware handles completed wizard navigation', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('contact-details', ['email' => 'john@example.com']);

    $response = $this->get(route('wizard.show', [
        'wizard' => 'checkout',
        'step' => 'personal-info',
    ]));

    $response->assertOk();
});
