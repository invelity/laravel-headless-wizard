<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Invelity\WizardPackage\Models\WizardProgress;
use Invelity\WizardPackage\Storage\VisitorScope;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'wizard.storage' => 'database',
        'wizard.wizards.checkout.steps' => [PersonalInfoStep::class],
    ]);
});

function checkoutInstance(string $owner): WizardProgress
{
    return WizardProgress::create([
        'wizard_id' => 'checkout',
        'session_id' => $owner,
        'current_step' => 'personal-info',
        'completed_steps' => [],
        'step_data' => ['personal-info' => ['name' => 'John']],
        'metadata' => [],
        'started_at' => now(),
    ]);
}

test('a visitor cannot read, change or delete another visitor\'s wizard instance', function () {
    $progress = checkoutInstance('session:another-visitor');
    $edit = ['wizard' => 'checkout', 'wizardId' => $progress->id, 'step' => 'personal-info'];

    $this->getJson(route('wizard.edit', $edit))->assertNotFound();
    $this->putJson(route('wizard.update', $edit), ['name' => 'Jane'])->assertNotFound();
    $this->deleteJson(route('wizard.destroy', ['wizard' => 'checkout', 'wizardId' => $progress->id]))->assertNotFound();

    expect(WizardProgress::find($progress->id))
        ->not->toBeNull()
        ->step_data->toBe(['personal-info' => ['name' => 'John']]);
});

test('a visitor can edit and delete their own wizard instance', function () {
    $progress = checkoutInstance((new VisitorScope)->key());

    $this->getJson(route('wizard.edit', ['wizard' => 'checkout', 'wizardId' => $progress->id, 'step' => 'personal-info']))
        ->assertOk()
        ->assertJsonPath('data.is_edit_mode', true);

    $this->deleteJson(route('wizard.destroy', ['wizard' => 'checkout', 'wizardId' => $progress->id]))
        ->assertOk();

    expect(WizardProgress::find($progress->id))->toBeNull();
});
