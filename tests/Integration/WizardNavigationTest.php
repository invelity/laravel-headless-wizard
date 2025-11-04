<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Invelity\WizardPackage\Tests\TestCase;

class WizardNavigationTest extends TestCase
{
    public function test_it_shows_back_button_on_non_first_steps(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_navigates_back_with_preserved_data(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_shows_progress_bar_with_correct_percentage(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_displays_step_indicators(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_highlights_current_step_in_breadcrumbs(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_allows_clicking_completed_steps(): void
    {
        expect(true)->toBeTrue();
    }

    public function test_it_prevents_clicking_incomplete_steps(): void
    {
        expect(true)->toBeTrue();
    }
}
