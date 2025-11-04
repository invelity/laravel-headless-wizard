<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Illuminate\Support\Facades\Config;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;
use Invelity\WizardPackage\Tests\TestCase;

class HeadlessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('wizard.wizards.test-wizard.steps', [
            PersonalInfoStep::class,
        ]);
    }

    public function test_controllers_return_json_not_views(): void
    {
        $response = $this->get('/wizard/test-wizard/personal-info');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_step_processing_returns_json(): void
    {
        $this->get('/wizard/test-wizard/personal-info');

        $response = $this->post('/wizard/test-wizard/personal-info', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_wizard_completion_returns_json(): void
    {
        $this->get('/wizard/test-wizard/personal-info');

        $this->post('/wizard/test-wizard/personal-info', [
            'name' => 'John Doe',
        ]);

        $response = $this->post('/wizard/test-wizard/complete');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_no_view_content_in_responses(): void
    {
        $response = $this->get('/wizard/test-wizard/personal-info');

        $response->assertStatus(200);
        $this->assertStringNotContainsString('<html>', $response->getContent());
        $this->assertStringNotContainsString('<!DOCTYPE', $response->getContent());
    }
}
