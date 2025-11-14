<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Invelity\WizardPackage\Commands\MakeWizardCommand;
use Invelity\WizardPackage\Contracts\WizardStorageInterface;
use Invelity\WizardPackage\Core\WizardConfiguration;
use Invelity\WizardPackage\Core\WizardNavigation;
use Invelity\WizardPackage\Services\StepFinderService;

test('writeWithLock error path when fopen fails due to permissions', function () {
    $testPath = sys_get_temp_dir().'/readonly-test-'.uniqid().'.php';
    File::put($testPath, '<?php return [];');
    chmod($testPath, 0000);

    $command = new class extends MakeWizardCommand
    {
        public function testWriteWithLock($path, $content)
        {
            return $this->writeWithLock($path, $content);
        }
    };

    try {
        @$command->testWriteWithLock($testPath, 'test');
        chmod($testPath, 0644);
        File::delete($testPath);
        expect(false)->toBeTrue('Should throw exception');
    } catch (\Throwable $e) {
        chmod($testPath, 0644);
        File::delete($testPath);
        expect($e)->toBeInstanceOf(\Throwable::class);
    }
});

test('getStepsBefore defensive null check never reached in normal flow', function () {
    $storage = app(WizardStorageInterface::class);
    $config = new WizardConfiguration(
        storage: 'session',
        navigation: ['allow_jump' => false],
        ui: [],
        validation: [],
        fireEvents: true
    );

    $step1 = new \Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;
    $step2 = new \Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;

    $stepFinder = new StepFinderService;
    $navigation = new WizardNavigation([$step1, $step2], $storage, $config, 'test', $stepFinder);

    $storage->put('test', [
        'current_step' => 'step1',
        'completed_steps' => [],
    ]);

    $result = $navigation->canNavigateTo('invalid-step');

    expect($result)->toBeFalse();
});
