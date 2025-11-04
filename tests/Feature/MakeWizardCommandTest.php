<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Feature;

use Illuminate\Support\Facades\File;
use WebSystem\WizardPackage\Tests\TestCase;

class MakeWizardCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupGeneratedFiles();
        $this->setupConfigFile();
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

        $configPath = config_path('wizard-package.php');
        if (File::exists($configPath.'.backup')) {
            File::delete($configPath.'.backup');
        }
    }

    protected function setupConfigFile(): void
    {
        $configPath = config_path('wizard-package.php');
        $configDir = dirname($configPath);

        if (! File::isDirectory($configDir)) {
            File::makeDirectory($configDir, 0755, true);
        }

        $config = [
            'storage' => [
                'driver' => 'session',
                'ttl' => 3600,
            ],
            'wizards' => [],
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
            ->expectsOutput(__('✓ Wizard class created: app/Wizards/{class}.php', ['class' => 'Onboarding']))
            ->assertSuccessful();

        $this->assertFileExists(app_path('Wizards/Onboarding.php'));
    }

    public function test_command_accepts_wizard_name_as_argument(): void
    {
        $this->artisan('wizard:make', ['name' => 'Registration'])
            ->expectsOutput(__('✓ Wizard class created: app/Wizards/{class}.php', ['class' => 'Registration']))
            ->assertSuccessful();

        $this->assertFileExists(app_path('Wizards/Registration.php'));
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
            ->expectsOutput(__('✓ Wizard class created: app/Wizards/{class}.php', ['class' => 'Onboarding']))
            ->assertSuccessful();
    }

    public function test_command_registers_wizard_in_config(): void
    {
        $this->artisan('wizard:make', ['name' => 'Onboarding'])->run();

        $config = require config_path('wizard-package.php');

        $this->assertArrayHasKey('onboarding', $config['wizards']);
        $this->assertEquals('App\Wizards\Onboarding', $config['wizards']['onboarding']['class']);
    }

    public function test_command_clears_config_cache_after_registration(): void
    {
        $this->artisan('wizard:make', ['name' => 'Onboarding'])
            ->expectsOutput('✓ Config cache cleared')
            ->assertSuccessful();
    }

    public function test_generated_wizard_class_has_correct_structure(): void
    {
        $this->artisan('wizard:make', ['name' => 'Onboarding'])->run();

        $content = File::get(app_path('Wizards/Onboarding.php'));

        $this->assertStringContainsString('namespace App\Wizards;', $content);
        $this->assertStringContainsString('class Onboarding', $content);
        $this->assertStringContainsString("return 'onboarding'", $content);
        $this->assertStringContainsString("return 'Onboarding'", $content);
    }
}
