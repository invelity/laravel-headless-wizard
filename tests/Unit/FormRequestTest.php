<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Invelity\WizardPackage\Tests\TestCase;

class FormRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupGeneratedFiles();
        $this->setupWizardConfig();
        Artisan::call('config:clear');

        $this->app['config']->set('wizard-package', require config_path('wizard-package.php'));
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
        $configPath = config_path('wizard-package.php');
        $configDir = dirname($configPath);

        if (! File::isDirectory($configDir)) {
            File::makeDirectory($configDir, 0755, true);
        }

        $config = [
            'storage' => ['driver' => 'session', 'ttl' => 3600],
            'wizards' => [
                'test-wizard' => [
                    'class' => 'App\Wizards\TestWizard',
                    'steps' => [],
                ],
            ],
            'routes' => ['enabled' => true, 'prefix' => 'wizard', 'middleware' => ['web']],
        ];

        File::put($configPath, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n");
    }

    public function test_form_request_has_rules_method(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'UserInfo',
            '--wizard' => 'test-wizard',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'User Information')
            ->assertSuccessful();

        $requestPath = app_path('Http/Requests/Wizards/UserInfoRequest.php');

        require_once $requestPath;

        $requestClass = 'App\Http\Requests\Wizards\UserInfoRequest';
        $this->assertTrue(method_exists($requestClass, 'rules'));
        $this->assertTrue(method_exists($requestClass, 'authorize'));
    }

    public function test_form_request_authorize_defaults_to_true(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'ProfileInfo',
            '--wizard' => 'test-wizard',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Profile Information')
            ->assertSuccessful();

        $requestPath = app_path('Http/Requests/Wizards/ProfileInfoRequest.php');
        $requestContent = File::get($requestPath);

        $this->assertStringContainsString('return true;', $requestContent);
    }

    public function test_form_request_rules_returns_array(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'ContactDetails',
            '--wizard' => 'test-wizard',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Contact Details')
            ->assertSuccessful();

        $requestPath = app_path('Http/Requests/Wizards/ContactDetailsRequest.php');

        require_once $requestPath;

        $requestClass = 'App\Http\Requests\Wizards\ContactDetailsRequest';
        $request = new $requestClass;

        $this->assertIsArray($request->rules());
    }

    public function test_form_request_extends_laravel_form_request(): void
    {
        $this->artisan('wizard:make-step', [
            'name' => 'AddressInfo',
            '--wizard' => 'test-wizard',
            '--order' => 1,
            '--optional' => false,
        ])
            ->expectsQuestion('What is the step title?', 'Address Information')
            ->assertSuccessful();

        $requestPath = app_path('Http/Requests/Wizards/AddressInfoRequest.php');

        require_once $requestPath;

        $requestClass = 'App\Http\Requests\Wizards\AddressInfoRequest';

        $this->assertTrue(is_subclass_of($requestClass, 'Illuminate\Foundation\Http\FormRequest'));
    }
}
