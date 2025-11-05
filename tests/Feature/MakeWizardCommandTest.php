<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Feature;

use Illuminate\Support\Facades\File;
use Invelity\WizardPackage\Tests\TestCase;

class MakeWizardCommandTest extends TestCase
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

        parent::tearDown();
    }

    protected function cleanupGeneratedFiles(): void
    {
        if (File::exists(app_path('Wizards/Onboarding.php'))) {
            File::delete(app_path('Wizards/Onboarding.php'));
        }

        if (File::exists(app_path('Wizards/Registration.php'))) {
            File::delete(app_path('Wizards/Registration.php'));
        }

        if (File::exists(app_path('Wizards'))) {
            File::deleteDirectory(app_path('Wizards'));
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
            'storage' => [
                'driver' => 'session',
                'ttl' => 3600,
            ],
            'routes' => [
                'enabled' => true,
                'prefix' => 'wizard',
                'middleware' => ['web'],
            ],
        ];

        File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");
    }

    public function test_command_prompts_for_wizard_name_interactively(): void
    {
        $this->artisan('wizard:make')
            ->expectsQuestion('What is the wizard name?', 'Onboarding')
            ->expectsOutput(__('✓ Wizard class created: app/Wizards/{wizard}Wizard/{class}.php', ['wizard' => 'Onboarding', 'class' => 'Onboarding']))
            ->assertSuccessful();

        $this->assertFileExists(app_path('Wizards/OnboardingWizard/Onboarding.php'));
        $this->assertDirectoryExists(app_path('Wizards/OnboardingWizard/Steps'));
    }

    public function test_command_accepts_wizard_name_as_argument(): void
    {
        $this->artisan('wizard:make', ['name' => 'Registration'])
            ->expectsOutput(__('✓ Wizard class created: app/Wizards/{wizard}Wizard/{class}.php', ['wizard' => 'Registration', 'class' => 'Registration']))
            ->assertSuccessful();

        $this->assertFileExists(app_path('Wizards/RegistrationWizard/Registration.php'));
        $this->assertDirectoryExists(app_path('Wizards/RegistrationWizard/Steps'));
    }

    public function test_command_validates_pascal_case_wizard_name(): void
    {
        $result = $this->artisan('wizard:make', ['name' => 'onboarding']);

        $result->assertFailed();
    }

    public function test_command_prevents_duplicate_wizard_names(): void
    {
        $this->artisan('wizard:make', ['name' => 'Onboarding'])->run();

        $this->artisan('wizard:make', ['name' => 'Onboarding'])
            ->expectsOutput(__('Wizard \':class\' already exists. Use --force to overwrite.', ['class' => 'Onboarding']))
            ->assertFailed();
    }

    public function test_command_overwrites_with_force_flag(): void
    {
        $this->artisan('wizard:make', ['name' => 'Onboarding'])->run();

        $this->artisan('wizard:make', ['name' => 'Onboarding', '--force' => true])
            ->expectsOutput(__('✓ Wizard class created: app/Wizards/{wizard}Wizard/{class}.php', ['wizard' => 'Onboarding', 'class' => 'Onboarding']))
            ->assertSuccessful();
    }

    public function test_command_creates_wizard_directory_structure(): void
    {
        $this->artisan('wizard:make', ['name' => 'Onboarding'])->run();

        $this->assertDirectoryExists(app_path('Wizards/OnboardingWizard'));
        $this->assertDirectoryExists(app_path('Wizards/OnboardingWizard/Steps'));
        $this->assertFileExists(app_path('Wizards/OnboardingWizard/Onboarding.php'));
    }

    public function test_command_shows_next_steps_instructions(): void
    {
        $this->artisan('wizard:make', ['name' => 'Onboarding'])
            ->expectsOutput(__('  • Generate first step: php artisan wizard:make-step {wizard}', ['wizard' => 'Onboarding']))
            ->assertSuccessful();
    }

    public function test_generated_wizard_class_has_correct_structure(): void
    {
        $this->artisan('wizard:make', ['name' => 'Onboarding'])->run();

        $content = File::get(app_path('Wizards/OnboardingWizard/Onboarding.php'));

        $this->assertStringContainsString('namespace App\\Wizards\\OnboardingWizard;', $content);
        $this->assertStringContainsString('class Onboarding', $content);
        $this->assertStringContainsString("return 'onboarding'", $content);
        $this->assertStringContainsString("return 'Onboarding'", $content);
    }
}
