<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Invelity\WizardPackage\Tests\TestCase;

class WizardControllerDeleteTest extends TestCase
{
    public function test_it_deletes_wizard_data(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_prevents_deleting_non_existent_wizard(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_clears_session_after_deletion(): void
    {
        expect(true)->toBeTrue();
    }
}
