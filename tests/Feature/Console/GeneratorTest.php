<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Invelity\WizardPackage\Facades\Wizard;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

uses(InteractsWithPublishedFiles::class);

beforeEach(function () {
    $this->files = [
        'app/Wizards/*.php',
        'app/Wizards/Steps/*.php',
        'app/Wizards/Steps/Order/*.php',
        'app/Http/Requests/Wizards/*.php',
        'app/Http/Requests/Wizards/Order/*.php',
        'stubs/wizard.stub',
        'stubs/step.stub',
        'resources/views/order/*.blade.php',
        'resources/views/checkout/steps/*.blade.php',
    ];
});

it('creates a wizard class', function () {
    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->assertSuccessful();

    $this->assertFileContains([
        'namespace App\Wizards;',
        'use Invelity\WizardPackage\Wizard;',
        'class OrderWizard extends Wizard',
        'protected array $steps = [',
    ], 'app/Wizards/OrderWizard.php');
});

it('asks for the name of the wizard', function () {
    $this->artisan('wizard:make')
        ->expectsQuestion('What should the wizard be named?', 'OnboardingWizard')
        ->assertSuccessful();

    $this->assertFilenameExists('app/Wizards/OnboardingWizard.php');
});

it('does not overwrite a wizard unless forced', function () {
    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->assertSuccessful();
    file_put_contents(app_path('Wizards/OrderWizard.php'), '<?php // edited');

    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->expectsOutputToContain('Wizard already exists.');
    $this->assertFileContains(['// edited'], 'app/Wizards/OrderWizard.php');

    $this->artisan('wizard:make', ['name' => 'OrderWizard', '--force' => true])->assertSuccessful();
    $this->assertFileContains(['class OrderWizard extends Wizard'], 'app/Wizards/OrderWizard.php');
});

it('prefers a published stub', function () {
    File::ensureDirectoryExists(base_path('stubs'));
    file_put_contents(base_path('stubs/wizard.stub'), "<?php\n\nnamespace {{ namespace }};\n\n// custom stub\nclass {{ class }} {}\n");

    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->assertSuccessful();

    $this->assertFileContains(['// custom stub', 'class OrderWizard {}'], 'app/Wizards/OrderWizard.php');
});

it('creates a step with the form request that validates it', function () {
    $this->artisan('wizard:make-step', ['name' => 'SummaryStep'])->assertSuccessful();

    $this->assertFileContains([
        'namespace App\Wizards\Steps;',
        'use App\Http\Requests\Wizards\SummaryRequest;',
        'class SummaryStep extends Step',
        'protected ?string $formRequest = SummaryRequest::class;',
        'public function handle(array $data): array',
    ], 'app/Wizards/Steps/SummaryStep.php');
    $this->assertFileNotContains(['$optional'], 'app/Wizards/Steps/SummaryStep.php');

    $this->assertFileContains([
        'namespace App\Http\Requests\Wizards;',
        'class SummaryRequest extends FormRequest',
        'public function rules(): array',
    ], 'app/Http/Requests/Wizards/SummaryRequest.php');
    $this->assertFileNotContains(['public function authorize'], 'app/Http/Requests/Wizards/SummaryRequest.php');
});

it('creates optional steps', function () {
    $this->artisan('wizard:make-step', ['name' => 'NewsletterStep', '--optional' => true])->assertSuccessful();

    $this->assertFileContains(['protected bool $optional = true;'], 'app/Wizards/Steps/NewsletterStep.php');
});

it('creates display-only steps without a form request', function () {
    $this->artisan('wizard:make-step', ['name' => 'ConfirmationStep', '--display' => true])->assertSuccessful();

    $this->assertFileContains(['protected bool $displayOnly = true;'], 'app/Wizards/Steps/ConfirmationStep.php');
    $this->assertFileNotContains(['formRequest', 'handle'], 'app/Wizards/Steps/ConfirmationStep.php');
    $this->assertFilenameNotExists('app/Http/Requests/Wizards/ConfirmationRequest.php');
});

it('mirrors nested step names in the form request', function () {
    $this->artisan('wizard:make-step', ['name' => 'Order/SummaryStep'])->assertSuccessful();

    $this->assertFileContains([
        'namespace App\Wizards\Steps\Order;',
        'use App\Http\Requests\Wizards\Order\SummaryRequest;',
    ], 'app/Wizards/Steps/Order/SummaryStep.php');
    $this->assertFileContains(['namespace App\Http\Requests\Wizards\Order;'], 'app/Http/Requests/Wizards/Order/SummaryRequest.php');
});

it('keeps an existing form request unless forced', function () {
    File::ensureDirectoryExists(app_path('Http/Requests/Wizards'));
    file_put_contents(app_path('Http/Requests/Wizards/SummaryRequest.php'), '<?php // edited');

    $this->artisan('wizard:make-step', ['name' => 'SummaryStep'])
        ->expectsOutputToContain('already exists')
        ->assertSuccessful();

    $this->assertFileContains(['class SummaryStep extends Step'], 'app/Wizards/Steps/SummaryStep.php');
    $this->assertFileContains(['// edited'], 'app/Http/Requests/Wizards/SummaryRequest.php');

    $this->artisan('wizard:make-step', ['name' => 'SummaryStep', '--force' => true])->assertSuccessful();

    $this->assertFileContains(['class SummaryRequest extends FormRequest'], 'app/Http/Requests/Wizards/SummaryRequest.php');
});

it('adds steps to a wizard in order', function () {
    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->assertSuccessful();

    $this->artisan('wizard:make-step', ['name' => 'CartStep', '--wizard' => 'OrderWizard'])
        ->expectsOutputToContain('added to wizard')
        ->assertSuccessful();
    $this->artisan('wizard:make-step', ['name' => 'ThanksStep', '--wizard' => 'OrderWizard', '--display' => true])->assertSuccessful();
    $this->artisan('wizard:make-step', ['name' => 'CartStep', '--wizard' => 'OrderWizard', '--force' => true])
        ->expectsOutputToContain('already part of wizard');

    expect(file_get_contents(app_path('Wizards/OrderWizard.php')))->toBe(<<<'PHP'
        <?php

        namespace App\Wizards;

        use App\Wizards\Steps\CartStep;
        use App\Wizards\Steps\ThanksStep;
        use Invelity\WizardPackage\Contracts\Step;
        use Invelity\WizardPackage\Wizard;

        class OrderWizard extends Wizard
        {
            /**
             * The steps of the wizard, in order.
             *
             * @var list<class-string<Step>>
             */
            protected array $steps = [
                CartStep::class,
                ThanksStep::class,
            ];
        }

        PHP);
});

it('refers to a step by its full name when its short name is taken', function () {
    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->assertSuccessful();
    $this->artisan('wizard:make-step', ['name' => 'Order/CartStep', '--wizard' => 'OrderWizard'])->assertSuccessful();
    $this->artisan('wizard:make-step', ['name' => 'CartStep', '--wizard' => 'OrderWizard'])->assertSuccessful();

    $this->assertFileContains([
        'use App\Wizards\Steps\Order\CartStep;',
        "CartStep::class,\n        \\App\Wizards\Steps\CartStep::class,",
    ], 'app/Wizards/OrderWizard.php');
});

it('tells how to add a step to a wizard it cannot find', function () {
    $this->artisan('wizard:make-step', ['name' => 'CartStep', '--wizard' => 'MissingWizard'])
        ->expectsOutputToContain('Wizard [App\Wizards\MissingWizard] does not exist. Add \App\Wizards\Steps\CartStep::class to its $steps.')
        ->assertSuccessful();
});

it('asks for the name and the kind of the step', function () {
    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->assertSuccessful();

    $this->artisan('wizard:make-step')
        ->expectsQuestion('What should the step be named?', 'NewsletterStep')
        ->expectsChoice('Which wizard should the step be added to?', 'OrderWizard', ['' => 'None', 'OrderWizard' => 'OrderWizard'])
        ->expectsChoice('What kind of step is it?', 'optional', [
            'input' => 'It takes input',
            'optional' => 'It takes input and may be skipped',
            'display' => 'It only displays information',
        ])
        ->assertSuccessful();

    $this->assertFileContains(['protected bool $optional = true;'], 'app/Wizards/Steps/NewsletterStep.php');
    $this->assertFileContains(['use App\Wizards\Steps\NewsletterStep;', '        NewsletterStep::class,'], 'app/Wizards/OrderWizard.php');
});

it('creates the view of the step with make:view', function () {
    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->assertSuccessful();
    $this->artisan('wizard:make-step', ['name' => 'Order/SummaryStep', '--wizard' => 'OrderWizard', '--view' => null])
        ->assertSuccessful();

    $this->assertFilenameExists('resources/views/order/summary.blade.php');
});

it('creates the view of the step under the given name', function () {
    $this->artisan('wizard:make-step', ['name' => 'SummaryStep', '--view' => 'checkout.steps.summary'])->assertSuccessful();

    $this->assertFilenameExists('resources/views/checkout/steps/summary.blade.php');
});

it('needs a view name or a wizard to name the view', function () {
    $this->artisan('wizard:make-step', ['name' => 'SummaryStep', '--view' => null])
        ->expectsOutputToContain('Name the view (--view=order.summary) or the wizard (--wizard=OrderWizard) to create it.')
        ->assertSuccessful();
});

it('creates no view unless asked to', function () {
    $this->artisan('wizard:make', ['name' => 'OrderWizard'])->assertSuccessful();
    $this->artisan('wizard:make-step', ['name' => 'SummaryStep', '--wizard' => 'OrderWizard'])->assertSuccessful();

    $this->assertFilenameNotExists('resources/views/order/summary.blade.php');
});

it('generates a wizard that runs', function () {
    $this->artisan('wizard:make', ['name' => 'CheckoutWizard'])->assertSuccessful();
    $this->artisan('wizard:make-step', ['name' => 'BasketStep', '--wizard' => 'CheckoutWizard'])->assertSuccessful();
    $this->artisan('wizard:make-step', ['name' => 'ReceiptStep', '--wizard' => 'CheckoutWizard', '--display' => true])->assertSuccessful();

    require_once app_path('Http/Requests/Wizards/BasketRequest.php');
    require_once app_path('Wizards/Steps/BasketStep.php');
    require_once app_path('Wizards/Steps/ReceiptStep.php');
    require_once app_path('Wizards/CheckoutWizard.php');

    $wizard = Wizard::for('App\Wizards\CheckoutWizard');

    expect($wizard->steps()->ids())->toBe(['basket', 'receipt'])
        ->and($wizard->process('basket', ['ignored' => true]))->toBe([])
        ->and($wizard->complete())->toBe(['basket' => []])
        ->and($wizard->current()?->id())->toBe('receipt');
});
