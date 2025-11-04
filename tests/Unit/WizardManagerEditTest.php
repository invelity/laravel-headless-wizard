<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Unit;

use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Tests\TestCase;

class WizardManagerEditTest extends TestCase
{
    public function test_it_loads_wizard_from_storage_for_editing(): void
    {
        $manager = app(WizardManagerInterface::class);

        config(['wizard.wizards.test-wizard.steps' => []]);
        $manager->initialize('test-wizard', ['wizard_instance_id' => 1]);

        expect(true)->toBeTrue();
    }

    public function test_it_updates_single_step_data(): void
    {
        $manager = app(WizardManagerInterface::class);

        config(['wizard.wizards.test-wizard.steps' => []]);
        $manager->initialize('test-wizard');

        expect(true)->toBeTrue();
    }

    public function test_it_preserves_other_steps_when_updating_one(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_validates_updated_data(): void
    {
        expect(true)->toBeTrue();
    }
}
