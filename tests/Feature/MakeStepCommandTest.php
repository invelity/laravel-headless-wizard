<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Feature;

use Illuminate\Support\Facades\File;
use Invelity\WizardPackage\Tests\TestCase;

class MakeStepCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupGeneratedFiles();
        $this->setupConfigFile();

        $config = require config_path('wizard.php');
        config(['wizard' => $config]);
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();
        $this->setupConfigFile();

        parent::tearDown();
    }

    protected function cleanupGeneratedFiles(): void
    {
        if (File::exists(app_path('Wizards'))) {
            File::deleteDirectory(app_path('Wizards'));
        }

        if (File::exists(app_path('Http/Requests/Wizards'))) {
            File::deleteDirectory(app_path('Http/Requests/Wizards'));
        }

        $configPath = config_path('wizard.php');
        if (File::exists($configPath.'.backup')) {
            File::delete($configPath.'.backup');
        }
    }

    protected function setupConfigFile(): void
    {
        $configPath = config_path('wizard.php');
        $configDir = dirname($configPath);

        if (! File::isDirectory($configDir)) {
            File::makeDirectory($configDir, 0755, true);
        }

        $config = [
            'storage' => ['driver' => 'session', 'ttl' => 3600],
            'routes' => ['enabled' => true, 'prefix' => 'wizard', 'middleware' => ['web']],
        ];

        File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");

        File::makeDirectory(app_path('Wizards/OnboardingWizard'), 0755, true);
        File::put(app_path('Wizards/OnboardingWizard/Onboarding.php'), "<?php\n\nnamespace App\\Wizards\\OnboardingWizard;\n\nclass Onboarding {\n    public function getId(): string { return 'onboarding'; }\n}\n");

        File::makeDirectory(app_path('Wizards/RegistrationWizard'), 0755, true);
        File::put(app_path('Wizards/RegistrationWizard/Registration.php'), "<?php\n\nnamespace App\\Wizards\\RegistrationWizard;\n\nclass Registration {\n    public function getId(): string { return 'registration'; }\n}\n");
    }

    public function test_command_shows_error_when_no_wizards_exist(): void
    {
        if (File::exists(app_path('Wizards'))) {
            File::deleteDirectory(app_path('Wizards'));
        }

        $result = $this->artisan('wizard:make-step');
        $result->assertFailed();
    }

    public function test_command_prompts_for_wizard_selection(): void
    {
        $this->artisan('wizard:make-step', ['wizard' => 'Onboarding', 'name' => 'PersonalInfo', '--order' => 1, '--optional' => false])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->execute();

        $this->assertFileExists(app_path('Wizards/OnboardingWizard/Steps/PersonalInfoStep.php'));
        $this->assertFileExists(app_path('Http/Requests/Wizards/PersonalInfoRequest.php'));
    }

    public function test_command_accepts_all_arguments_and_options(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'Onboarding',
            'name' => 'ContactDetails',
            '--order' => 2,
            '--optional' => true,
        ])
            ->expectsQuestion('What is the step title?', 'Contact Details')
            ->execute();

        $this->assertFileExists(app_path('Wizards/OnboardingWizard/Steps/ContactDetailsStep.php'));
    }

    public function test_command_auto_creates_step_file(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'Onboarding',
            'name' => 'PersonalInfo',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->execute();

        $this->assertFileExists(app_path('Wizards/OnboardingWizard/Steps/PersonalInfoStep.php'));

        $content = File::get(app_path('Wizards/OnboardingWizard/Steps/PersonalInfoStep.php'));
        $this->assertStringContainsString('namespace App\\Wizards\\OnboardingWizard\\Steps', $content);
        $this->assertStringContainsString('class PersonalInfoStep', $content);
    }

    public function test_command_generates_form_request_class(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'Onboarding',
            'name' => 'PersonalInfo',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->execute();

        $this->assertFileExists(app_path('Http/Requests/Wizards/PersonalInfoRequest.php'));

        $content = File::get(app_path('Http/Requests/Wizards/PersonalInfoRequest.php'));

        $this->assertStringContainsString('namespace App\\Http\\Requests\\Wizards;', $content);
        $this->assertStringContainsString('class PersonalInfoRequest', $content);
        $this->assertStringContainsString('public function rules()', $content);
    }

    public function test_command_validates_pascal_case_step_name(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'Onboarding',
            'name' => 'personalInfo',
        ])->assertFailed();
    }

    public function test_command_prevents_duplicate_steps(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'Onboarding',
            'name' => 'PersonalInfo',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->execute();

        $result = $this->artisan('wizard:make-step', [
            'wizard' => 'Onboarding',
            'name' => 'PersonalInfo',
            '--order' => 1,
        ]);
        $result->assertFailed();
    }

    public function test_command_overwrites_with_force_flag(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'Onboarding',
            'name' => 'PersonalInfo',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->execute();

        $this->artisan('wizard:make-step', [
            'wizard' => 'Onboarding',
            'name' => 'PersonalInfo',
            '--order' => 1,
            '--force' => true,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->execute();
    }
}
