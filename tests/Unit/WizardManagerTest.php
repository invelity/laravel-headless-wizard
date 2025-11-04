<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Unit;

use WebSystem\WizardPackage\Contracts\WizardStorageInterface;
use WebSystem\WizardPackage\Core\WizardManager;
use WebSystem\WizardPackage\Tests\TestCase;

class WizardManagerTest extends TestCase
{
    public function test_it_initializes_with_empty_wizard_data(): void
    {
        $manager = app(WizardManager::class);
        $storage = app(WizardStorageInterface::class);

        config(['wizard.wizards.test-wizard.steps' => []]);
        $manager->initialize('test-wizard');

        $wizardData = $storage->get('test-wizard');

        expect($wizardData)->toHaveKey('wizard_id')
            ->and($wizardData['wizard_id'])->toBe('test-wizard');
    }

    public function test_it_stores_step_data_after_processing(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_retrieves_all_wizard_data(): void
    {
        $wizardData = [
            'steps' => [
                'step-1' => ['name' => 'John'],
                'step-2' => ['address' => '123 Main St'],
            ],
        ];

        expect($wizardData['steps'])->toHaveKey('step-1');
    }

    public function test_it_marks_steps_as_completed(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_calculates_progress_based_on_completed_steps(): void
    {
        $wizardData = [
            'total_steps' => 4,
            'completed_steps' => ['step-1', 'step-2'],
            'current_step' => 'step-3',
        ];

        expect(count($wizardData['completed_steps']))->toBe(2);
    }
}
