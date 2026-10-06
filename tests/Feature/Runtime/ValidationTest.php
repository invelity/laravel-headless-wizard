<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Step;
use Invelity\WizardPackage\Wizard as BaseWizard;

/**
 * Create a wizard with the given single step.
 */
function wizardWithStep(Step $step): BaseWizard
{
    app()->instance($step::class, $step);

    return Wizard::for(new class([$step::class]) extends BaseWizard
    {
        protected string $name = 'single';

        /**
         * @param  list<class-string<Invelity\WizardPackage\Contracts\Step>>  $steps
         */
        public function __construct(array $steps)
        {
            $this->steps = $steps;
        }
    });
}

/**
 * Create a wizard whose only step validates with the given form request.
 *
 * @param  class-string<FormRequest>|null  $request
 */
function wizardValidatedBy(?string $request): BaseWizard
{
    return wizardWithStep(new class($request) extends Step
    {
        protected string $id = 'details';

        public function __construct(?string $request)
        {
            $this->formRequest = $request;
        }
    });
}

it('runs the whole form request lifecycle', function () {
    $request = new class extends FormRequest
    {
        /** @var list<string> */
        public static array $calls = [];

        public function authorize(): bool
        {
            self::$calls[] = 'authorize';

            return true;
        }

        public function rules(): array
        {
            self::$calls[] = 'rules';

            return ['name' => ['required', 'string']];
        }

        protected function prepareForValidation(): void
        {
            self::$calls[] = 'prepare';

            $this->merge(['name' => trim((string) $this->input('name'))]);
        }

        protected function passedValidation(): void
        {
            self::$calls[] = 'passed';
        }
    };

    $data = wizardValidatedBy($request::class)->process('details', ['name' => '  Jane  ', 'extra' => 'dropped']);

    expect($data)->toBe(['name' => 'Jane'])
        ->and($request::$calls)->toBe(['prepare', 'authorize', 'rules', 'passed']);
});

it('refuses visitors the form request does not authorize', function () {
    $request = new class extends FormRequest
    {
        public function authorize(): bool
        {
            return false;
        }

        public function rules(): array
        {
            return [];
        }
    };

    wizardValidatedBy($request::class)->process('details', []);
})->throws(AuthorizationException::class);

it('runs the after hooks of the validator', function () {
    $request = new class extends FormRequest
    {
        public function rules(): array
        {
            return ['coupon' => ['required', 'string']];
        }

        public function after(): array
        {
            return [fn ($validator) => $validator->errors()->add('coupon', 'The coupon has expired.')];
        }
    };

    try {
        wizardValidatedBy($request::class)->process('details', ['coupon' => 'OLD']);
        $this->fail('The input passed.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe(['coupon' => ['The coupon has expired.']]);
    }
});

it('gives the form request the current user and route', function () {
    $request = new class extends FormRequest
    {
        public static mixed $order = null;

        public function authorize(): bool
        {
            return $this->user()?->getAuthIdentifier() === 7;
        }

        public function rules(): array
        {
            self::$order = $this->route('order');

            return ['note' => ['nullable', 'string']];
        }
    };

    $this->actingAs((new User)->forceFill(['id' => 7]));

    $current = Request::create('/orders/42');
    $this->app->instance('request', $current);
    $current->setRouteResolver(fn () => (new Route('GET', 'orders/{order}', []))->bind($current));

    expect(wizardValidatedBy($request::class)->process('details', ['note' => 'Ring twice']))->toBe(['note' => 'Ring twice'])
        ->and($request::$order)->toBe('42');
});

it('validates an HTTP request with its files', function () {
    $request = new class extends FormRequest
    {
        public function rules(): array
        {
            return ['avatar' => ['required', 'image'], 'name' => ['required']];
        }
    };

    $wizard = wizardWithStep(new class($request::class) extends Step
    {
        protected string $id = 'details';

        public function __construct(?string $request)
        {
            $this->formRequest = $request;
        }

        /**
         * @param  array<string, mixed>  $data
         * @return array<string, mixed>
         */
        public function handle(array $data): array
        {
            return ['name' => $data['name'], 'avatar' => $data['avatar']->getClientOriginalName()];
        }
    });

    $http = Request::create('/', 'POST', ['name' => 'Jane'], files: ['avatar' => UploadedFile::fake()->image('avatar.png')]);

    expect($wizard->process('details', $http))->toBe(['name' => 'Jane', 'avatar' => 'avatar.png']);
});

it('validates JSON requests', function () {
    $request = new class extends FormRequest
    {
        public function rules(): array
        {
            return ['tags' => ['required', 'array'], 'tags.*' => ['string']];
        }
    };

    $http = Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: (string) json_encode(['tags' => ['a', 'b']]));

    expect(wizardValidatedBy($request::class)->process('details', $http))->toBe(['tags' => ['a', 'b']]);
});

it('accepts no input for steps without a form request', function () {
    expect(wizardValidatedBy(null)->process('details', ['anything' => 'goes']))->toBe([]);
});
