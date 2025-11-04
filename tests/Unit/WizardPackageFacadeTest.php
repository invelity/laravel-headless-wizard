<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Unit;

use Invelity\WizardPackage\Contracts\WizardManagerInterface;
use Invelity\WizardPackage\Contracts\WizardNavigationInterface;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\Facades\WizardPackage as WizardPackageFacade;
use Invelity\WizardPackage\Tests\TestCase;
use Invelity\WizardPackage\ValueObjects\StepResult;
use Invelity\WizardPackage\ValueObjects\WizardProgressValue;
use Invelity\WizardPackage\Wizard;
use Mockery;

class WizardPackageFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockManager = Mockery::mock(WizardManagerInterface::class);
        $this->app->instance(Wizard::class, new Wizard($this->mockManager));
    }

    public function test_facade_delegates_initialize(): void
    {
        $this->mockManager->shouldReceive('initialize')
            ->once()
            ->with('test-wizard', ['key' => 'value']);

        WizardPackageFacade::initialize('test-wizard', ['key' => 'value']);
    }

    public function test_facade_delegates_get_current_step(): void
    {
        $mockStep = Mockery::mock(WizardStepInterface::class);

        $this->mockManager->shouldReceive('getCurrentStep')
            ->once()
            ->andReturn($mockStep);

        $result = WizardPackageFacade::getCurrentStep();

        $this->assertSame($mockStep, $result);
    }

    public function test_facade_delegates_get_step(): void
    {
        $mockStep = Mockery::mock(WizardStepInterface::class);

        $this->mockManager->shouldReceive('getStep')
            ->once()
            ->with('step-id')
            ->andReturn($mockStep);

        $result = WizardPackageFacade::getStep('step-id');

        $this->assertSame($mockStep, $result);
    }

    public function test_facade_delegates_process_step(): void
    {
        $mockResult = Mockery::mock(StepResult::class);

        $this->mockManager->shouldReceive('processStep')
            ->once()
            ->with('step-id', ['data' => 'value'])
            ->andReturn($mockResult);

        $result = WizardPackageFacade::processStep('step-id', ['data' => 'value']);

        $this->assertSame($mockResult, $result);
    }

    public function test_facade_delegates_navigate_to_step(): void
    {
        $this->mockManager->shouldReceive('navigateToStep')
            ->once()
            ->with('step-id');

        WizardPackageFacade::navigateToStep('step-id');
    }

    public function test_facade_delegates_get_next_step(): void
    {
        $mockStep = Mockery::mock(WizardStepInterface::class);

        $this->mockManager->shouldReceive('getNextStep')
            ->once()
            ->andReturn($mockStep);

        $result = WizardPackageFacade::getNextStep();

        $this->assertSame($mockStep, $result);
    }

    public function test_facade_delegates_get_previous_step(): void
    {
        $mockStep = Mockery::mock(WizardStepInterface::class);

        $this->mockManager->shouldReceive('getPreviousStep')
            ->once()
            ->andReturn($mockStep);

        $result = WizardPackageFacade::getPreviousStep();

        $this->assertSame($mockStep, $result);
    }

    public function test_facade_delegates_can_access_step(): void
    {
        $this->mockManager->shouldReceive('canAccessStep')
            ->once()
            ->with('step-id')
            ->andReturn(true);

        $result = WizardPackageFacade::canAccessStep('step-id');

        $this->assertTrue($result);
    }

    public function test_facade_delegates_get_progress(): void
    {
        $mockProgress = Mockery::mock(WizardProgressValue::class);

        $this->mockManager->shouldReceive('getProgress')
            ->once()
            ->andReturn($mockProgress);

        $result = WizardPackageFacade::getProgress();

        $this->assertSame($mockProgress, $result);
    }

    public function test_facade_delegates_get_all_data(): void
    {
        $this->mockManager->shouldReceive('getAllData')
            ->once()
            ->andReturn(['data' => 'value']);

        $result = WizardPackageFacade::getAllData();

        $this->assertEquals(['data' => 'value'], $result);
    }

    public function test_facade_delegates_complete(): void
    {
        $mockResult = Mockery::mock(StepResult::class);

        $this->mockManager->shouldReceive('complete')
            ->once()
            ->andReturn($mockResult);

        $result = WizardPackageFacade::complete();

        $this->assertSame($mockResult, $result);
    }

    public function test_facade_delegates_reset(): void
    {
        $this->mockManager->shouldReceive('reset')
            ->once();

        WizardPackageFacade::reset();
    }

    public function test_facade_delegates_load_from_storage(): void
    {
        $this->mockManager->shouldReceive('loadFromStorage')
            ->once()
            ->with('wizard-id', 123);

        WizardPackageFacade::loadFromStorage('wizard-id', 123);
    }

    public function test_facade_delegates_delete_wizard(): void
    {
        $this->mockManager->shouldReceive('deleteWizard')
            ->once()
            ->with('wizard-id', 123);

        WizardPackageFacade::deleteWizard('wizard-id', 123);
    }

    public function test_facade_delegates_get_navigation(): void
    {
        $mockNavigation = Mockery::mock(WizardNavigationInterface::class);

        $this->mockManager->shouldReceive('getNavigation')
            ->once()
            ->andReturn($mockNavigation);

        $result = WizardPackageFacade::getNavigation();

        $this->assertSame($mockNavigation, $result);
    }

    public function test_facade_delegates_skip_step(): void
    {
        $this->mockManager->shouldReceive('skipStep')
            ->once()
            ->with('step-id');

        WizardPackageFacade::skipStep('step-id');
    }
}
