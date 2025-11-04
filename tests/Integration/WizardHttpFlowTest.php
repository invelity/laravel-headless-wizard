<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\OptionalStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['wizard.wizards.registration' => [
        'steps' => [
            PersonalInfoStep::class,
            OptionalStep::class,
            ContactDetailsStep::class,
        ],
    ]]);
});

test('user can view first step of wizard', function () {
    $response = $this->get(route('wizard.show', [
        'wizard' => 'registration',
        'step' => 'personal-info',
    ]));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'wizard_id' => 'registration',
                'step' => [
                    'id' => 'personal-info',
                ],
            ],
        ]);
});

test('user can submit valid step data', function () {
    $response = $this->post(route('wizard.store', [
        'wizard' => 'registration',
        'step' => 'personal-info',
    ]), [
        'name' => 'John Doe',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);
});

test('user receives validation errors for invalid data', function () {
    $this->withoutExceptionHandling();
    
    expect(fn () => $this->post(route('wizard.store', [
        'wizard' => 'registration',
        'step' => 'personal-info',
    ]), [
        'name' => '',
    ]))->toThrow(\Invelity\WizardPackage\Exceptions\StepValidationException::class);
});

test('user can skip optional step via manager', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('registration');
    $manager->processStep('personal-info', ['name' => 'John']);

    $manager->skipStep('optional-step');

    $progress = $manager->getProgress();
    expect($progress->completedSteps)->toBeGreaterThanOrEqual(2);
});

test('user cannot skip required step', function () {
    session()->put('registration', [
        'wizard_id' => 'registration',
        'current_step' => 'contact-details',
        'completed_steps' => ['personal-info'],
        'steps' => ['personal-info' => ['name' => 'John']],
        'metadata' => [],
        'started_at' => now()->toIso8601String(),
    ]);

    $this->post(route('wizard.skip', [
        'wizard' => 'registration',
        'step' => 'contact-details',
    ]))->assertStatus(500);
});

test('user can complete wizard after all steps', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('registration');
    $manager->processStep('personal-info', ['name' => 'John']);
    $manager->processStep('optional-step', ['optional_field' => 'test']);
    $manager->processStep('contact-details', ['email' => 'john@example.com']);

    $result = $manager->complete();

    expect($result->success)->toBeTrue()
        ->and($result->data)->toHaveKey('personal-info')
        ->and($result->data)->toHaveKey('contact-details');
});

test('user cannot complete wizard with incomplete steps', function () {
    session()->put('registration', [
        'wizard_id' => 'registration',
        'current_step' => 'personal-info',
        'completed_steps' => ['personal-info'],
        'steps' => ['personal-info' => ['name' => 'John']],
        'metadata' => [],
        'started_at' => now()->toIso8601String(),
    ]);

    $response = $this->post(route('wizard.completed', 'registration'));

    $response->assertStatus(422);
});

test('user is redirected when accessing inaccessible step', function () {
    $response = $this->get(route('wizard.show', [
        'wizard' => 'registration',
        'step' => 'contact-details',
    ]));

    $response->assertStatus(403);
});

test('user data persists across steps', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('registration');
    
    $manager->processStep('personal-info', ['name' => 'Jane Doe']);
    $manager->processStep('optional-step', ['optional_field' => 'test value']);

    $allData = $manager->getAllData();
    expect($allData)->toHaveKey('personal-info')
        ->and($allData)->toHaveKey('optional-step')
        ->and($allData['personal-info']['name'])->toBe('Jane Doe')
        ->and($allData['optional-step']['optional_field'])->toBe('test value');
});

test('user can navigate back to edit completed steps', function () {
    session()->put('registration', [
        'wizard_id' => 'registration',
        'current_step' => 'optional-step',
        'completed_steps' => ['personal-info'],
        'steps' => ['personal-info' => ['name' => 'John']],
        'metadata' => [],
        'started_at' => now()->toIso8601String(),
    ]);

    $response = $this->get(route('wizard.show', [
        'wizard' => 'registration',
        'step' => 'personal-info',
    ]));

    $response->assertOk();
    
    $json = $response->json();
    expect($json['data']['step']['id'])->toBe('personal-info');
});
