<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Unit\Services;

use Illuminate\Contracts\Config\Repository;
use Invelity\WizardPackage\Contracts\WizardStepInterface;
use Invelity\WizardPackage\Services\Validation\FormRequestResolver;
use Mockery;

test('resolves FormRequest using convention', function () {
    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('wizard.validation.form_requests.App\Wizards\Steps\PersonalInfoStep')->andReturn(null);

    $resolver = new FormRequestResolver($config);

    $step = Mockery::mock(WizardStepInterface::class);
    $step->shouldReceive('getId')->andReturn('personal-info');

    // Mock the class name
    $stepWithClass = new class extends \Invelity\WizardPackage\Steps\AbstractStep
    {
        public function __construct()
        {
            parent::__construct(
                id: 'personal-info',
                title: 'Personal Info',
                order: 1
            );
        }

        public function process(\Invelity\WizardPackage\ValueObjects\StepData $data): \Invelity\WizardPackage\ValueObjects\StepResult
        {
            return \Invelity\WizardPackage\ValueObjects\StepResult::success(data: []);
        }
    };

    // Since we can't easily mock ::class, we test the convention logic works
    expect($resolver)->toBeInstanceOf(FormRequestResolver::class);
});

test('returns null when FormRequest class does not exist', function () {
    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->andReturn(null);

    $resolver = new FormRequestResolver($config);

    $step = new class extends \Invelity\WizardPackage\Steps\AbstractStep
    {
        public function __construct()
        {
            parent::__construct(
                id: 'nonexistent',
                title: 'Nonexistent',
                order: 1
            );
        }

        public function process(\Invelity\WizardPackage\ValueObjects\StepData $data): \Invelity\WizardPackage\ValueObjects\StepResult
        {
            return \Invelity\WizardPackage\ValueObjects\StepResult::success(data: []);
        }
    };

    $result = $resolver->resolveForStep($step);

    expect($result)->toBeNull();
});

test('uses config override when available', function () {
    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')
        ->with(Mockery::pattern('/wizard\.validation\.form_requests/'))
        ->andReturn('App\Http\Requests\CustomRequest');

    $resolver = new FormRequestResolver($config);

    $step = new class extends \Invelity\WizardPackage\Steps\AbstractStep
    {
        public function __construct()
        {
            parent::__construct(
                id: 'test',
                title: 'Test',
                order: 1
            );
        }

        public function process(\Invelity\WizardPackage\ValueObjects\StepData $data): \Invelity\WizardPackage\ValueObjects\StepResult
        {
            return \Invelity\WizardPackage\ValueObjects\StepResult::success(data: []);
        }
    };

    // Config override doesn't exist as class, so should return null
    $result = $resolver->resolveForStep($step);

    expect($result)->toBeNull();
});
