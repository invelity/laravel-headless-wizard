<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Contract;

use WebSystem\WizardPackage\Facades\Wizard;
use WebSystem\WizardPackage\Tests\TestCase;

class WizardFacadeTest extends TestCase
{
    /** @test */
    public function wizard_facade_resolves_to_wizard_manager(): void
    {
        $facade = Wizard::getFacadeRoot();

        expect($facade)
            ->toBeInstanceOf(\WebSystem\WizardPackage\Contracts\WizardManagerInterface::class);
    }

    /** @test */
    public function facade_can_initialize_wizard(): void
    {
        Wizard::initialize('test-wizard');

        expect(Wizard::getCurrentStep())->not->toBeNull();
    }

    /** @test */
    public function facade_can_access_all_manager_methods(): void
    {
        $methods = [
            'initialize',
            'getCurrentStep',
            'getStep',
            'processStep',
            'navigateToStep',
            'getNextStep',
            'getPreviousStep',
            'canAccessStep',
            'getProgress',
            'getAllData',
            'complete',
            'reset',
        ];

        $facade = Wizard::getFacadeRoot();

        foreach ($methods as $method) {
            expect(method_exists($facade, $method))
                ->toBeTrue("Method {$method} should exist on facade");
        }
    }
}
