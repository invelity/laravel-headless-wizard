<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Tests\Feature;

use Illuminate\Support\Facades\File;
use WebSystem\WizardPackage\Tests\TestCase;

class MakeStepCommandTest extends TestCase
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
            'storage' => ['driver' => 'session', 'ttl' => 3600],
            'wizards' => [
                'onboarding' => [
                    'class' => 'App\Wizards\Onboarding',
                    'steps' => [],
                ],
                'registration' => [
                    'class' => 'App\Wizards\Registration',
                    'steps' => [],
                ],
            ],
            'routes' => ['enabled' => true, 'prefix' => 'wizard', 'middleware' => ['web']],
        ];

        File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");
    }

    public function test_command_shows_error_when_no_wizards_exist(): void
    {
        $config = [
            'storage' => ['driver' => 'session', 'ttl' => 3600],
            'wizards' => [],
            'routes' => ['enabled' => true, 'prefix' => 'wizard', 'middleware' => ['web']],
        ];

        File::put(config_path('wizard-package.php'), "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");

        $this->app['config']->set('wizard-package', $config);

        $this->artisan('wizard:make-step')
            ->expectsOutput('No wizards found. Create a wizard first:')
            ->expectsOutput('php artisan wizard:make')
            ->assertFailed();
    }

    public function test_command_prompts_for_wizard_selection(): void
    {
        $this->artisan('wizard:make-step', ['name' => 'PersonalInfo', '--wizard' => 'onboarding', '--order' => 1, '--optional' => false])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->expectsOutput(__('✓ Step class created: app/Wizards/Steps/{class}.php', ['class' => 'PersonalInfoStep']))
            ->assertSuccessful();

        $this->assertFileExists(app_path('Wizards/Steps/PersonalInfoStep.php'));
        $this->assertFileExists(app_path('Http/Requests/Wizards/PersonalInfoRequest.php'));
    }

    public function test_command_accepts_all_arguments_and_options(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'ContactDetails',
            '--wizard' => 'onboarding',
            '--order' => 2,
            '--optional' => true,
        ])
            ->expectsQuestion('What is the step title?', 'Contact Details')
            ->assertSuccessful();

        $this->assertFileExists(app_path('Wizards/Steps/ContactDetailsStep.php'));
    }

    public function test_command_auto_registers_step_in_wizard_config(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'PersonalInfo',
            '--wizard' => 'onboarding',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->assertSuccessful();

        $config = require config_path('wizard-package.php');

        $this->assertArrayHasKey('onboarding', $config['wizards']);
        $this->assertContains('App\Wizards\Steps\PersonalInfoStep', $config['wizards']['onboarding']['steps']);
    }

    public function test_command_generates_form_request_class(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'PersonalInfo',
            '--wizard' => 'onboarding',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->assertSuccessful();

        $this->assertFileExists(app_path('Http/Requests/Wizards/PersonalInfoRequest.php'));

        $content = File::get(app_path('Http/Requests/Wizards/PersonalInfoRequest.php'));

        $this->assertStringContainsString('namespace App\Http\Requests\Wizards;', $content);
        $this->assertStringContainsString('class PersonalInfoRequest', $content);
        $this->assertStringContainsString('public function rules()', $content);
    }

    public function test_command_validates_pascal_case_step_name(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'personalInfo',
            '--wizard' => 'onboarding',
        ])->assertFailed();
    }

    public function test_command_prevents_duplicate_steps(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'PersonalInfo',
            '--wizard' => 'onboarding',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->assertSuccessful();

        $this->artisan('wizard:make-step', [
            'name' => 'PersonalInfo',
            '--wizard' => 'onboarding',
            '--order' => 1,
        ])
            ->expectsOutput("Step 'PersonalInfoStep' already exists. Use --force to overwrite.")
            ->assertFailed();
    }

    public function test_command_overwrites_with_force_flag(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'PersonalInfo',
            '--wizard' => 'onboarding',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->assertSuccessful();

        $this->artisan('wizard:make-step', [
            'name' => 'PersonalInfo',
            '--wizard' => 'onboarding',
            '--order' => 1,
            '--force' => true,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->assertSuccessful();
    }
}
