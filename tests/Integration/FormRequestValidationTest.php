<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Integration;

use Illuminate\Support\Facades\File;
use Invelity\WizardPackage\Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupGeneratedFiles();
        $this->setupWizardConfig();

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
        if (File::exists(app_path('Wizards'))) {
            File::deleteDirectory(app_path('Wizards'));
        }

        if (File::exists(app_path('Http/Requests/Wizards'))) {
            File::deleteDirectory(app_path('Http/Requests/Wizards'));
        }
    }

    protected function setupWizardConfig(): void
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

        File::makeDirectory(app_path('Wizards/TestWizardWizard'), 0755, true);
        File::put(app_path('Wizards/TestWizardWizard/TestWizard.php'), "<?php\n\nnamespace App\\Wizards\\TestWizardWizard;\n\nclass TestWizard {\n    public function getId(): string { return 'test-wizard'; }\n}\n");
    }

    public function test_validation_occurs_through_form_request(): void
    {
        $result = $this->artisan('wizard:make-step', [
            'wizard' => 'TestWizard',
            'name' => 'ContactInfo',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Contact Information');

        $exitCode = $result->run();

        // Debug: Check if wizard directory exists
        $this->assertTrue(
            \Illuminate\Support\Facades\File::isDirectory(app_path('Wizards/TestWizardWizard')),
            'TestWizardWizard directory should exist'
        );

        // Debug: Check step file
        $stepPath = app_path('Wizards/TestWizardWizard/Steps/ContactInfoStep.php');
        $this->assertFileExists($stepPath, 'Step file should be created');

        $requestPath = app_path('Http/Requests/Wizards/ContactInfoRequest.php');
        $this->assertFileExists($requestPath, 'FormRequest file should be created');

        $requestContent = File::get($requestPath);
        $this->assertStringContainsString('public function rules()', $requestContent);
        $this->assertStringContainsString('public function authorize()', $requestContent);
    }

    public function test_step_class_returns_form_request(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'TestWizard',
            'name' => 'PersonalInfo',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Personal Information')
            ->execute();

        $stepPath = app_path('Wizards/TestWizardWizard/Steps/PersonalInfoStep.php');
        $this->assertFileExists($stepPath);

        $stepContent = File::get($stepPath);

        $this->assertStringContainsString('public function getFormRequest()', $stepContent);
        $this->assertStringContainsString('PersonalInfoRequest::class', $stepContent);
    }

    public function test_form_request_validation_rules_are_customizable(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'TestWizard',
            'name' => 'EmailVerification',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Email Verification')
            ->execute();

        $requestPath = app_path('Http/Requests/Wizards/EmailVerificationRequest.php');
        $requestContent = File::get($requestPath);

        File::put($requestPath, str_replace(
            'return [
            // Add validation rules here
        ];',
            "return [
            'email' => 'required|email|unique:users,email',
        ];",
            $requestContent
        ));

        $updatedContent = File::get($requestPath);
        $this->assertStringContainsString("'email' => 'required|email|unique:users,email'", $updatedContent);
    }

    public function test_generated_form_request_has_correct_namespace(): void
    {
        $this->artisan('wizard:make-step', [
            'wizard' => 'TestWizard',
            'name' => 'AccountSetup',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Account Setup')
            ->execute();

        $requestPath = app_path('Http/Requests/Wizards/AccountSetupRequest.php');
        $requestContent = File::get($requestPath);

        $this->assertStringContainsString('namespace App\\Http\\Requests\\Wizards;', $requestContent);
        $this->assertStringContainsString('use Illuminate\\Foundation\\Http\\FormRequest;', $requestContent);
        $this->assertStringContainsString('class AccountSetupRequest extends FormRequest', $requestContent);
    }
}
