---
layout: default
title: Examples
nav_order: 6
---

# Examples

The package is headless: it validates, stores and navigates; you render. These recipes all use the `OrderWizard` from
[Wizards and steps]({{ site.baseurl }}/creating-wizards).

## Blade with your own controllers

```php
// routes/web.php
use App\Http\Controllers\OrderController;
use App\Wizards\OrderWizard;
use Invelity\WizardPackage\Http\Middleware\EnsureStepIsAccessible;

Route::middleware(['web', EnsureStepIsAccessible::using(OrderWizard::class)])->group(function () {
    Route::get('/order/{step}', [OrderController::class, 'show'])->name('order.step');
    Route::post('/order/{step}', [OrderController::class, 'store']);
});

Route::post('/order', [OrderController::class, 'complete'])->middleware('web')->name('order.complete');
```

```php
// app/Http/Controllers/OrderController.php
use App\Wizards\OrderWizard;
use Illuminate\Http\Request;

class OrderController
{
    public function show(OrderWizard $wizard, string $step)
    {
        return view("order.{$step}", [
            'step' => $wizard->step($step),
            'data' => $wizard->data($step),
            'navigation' => $wizard->navigation($step),
            'progress' => $wizard->progress(),
        ]);
    }

    public function store(Request $request, OrderWizard $wizard, string $step)
    {
        $wizard->process($step, $request);

        return to_route('order.step', $wizard->current()->id());
    }

    public function complete(OrderWizard $wizard)
    {
        $wizard->complete();   // listen to WizardCompleted to place the order

        return to_route('order.step', $wizard->current()->id());
    }
}
```

Link the steps to your routes once, on the wizard:

```php
class OrderWizard extends Wizard
{
    protected function stepUrl(Step $step): ?string
    {
        return route('order.step', $step->id());
    }
}
```

{% raw %}
```blade
{{-- resources/views/order/partials/navigation.blade.php --}}
<nav aria-label="Order progress">
    <div role="progressbar" aria-valuenow="{{ $progress->percentage() }}" aria-valuemin="0" aria-valuemax="100"
         style="width: {{ $progress->percentage() }}%"></div>

    <ol>
        @foreach ($navigation->items as $item)
            <li @class(['current' => $item->current, 'done' => $item->status->value !== 'pending'])>
                @if ($item->accessible && $item->url)
                    <a href="{{ $item->url }}">{{ $item->position }}. {{ $item->title }}</a>
                @else
                    <span>{{ $item->position }}. {{ $item->title }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
```
{% endraw %}

{% raw %}
```blade
{{-- resources/views/order/calculator.blade.php --}}
<form method="POST" action="{{ url("/order/{$step->id()}") }}">
    @csrf
    <input name="weight" value="{{ old('weight', $data['weight'] ?? '') }}">
    @error('weight') <p>{{ $message }}</p> @enderror

    @if ($navigation->previous)
        <a href="{{ route('order.step', $navigation->previous) }}">Back</a>
    @endif
    <button>Continue</button>
</form>
```
{% endraw %}

## Livewire

```php
use App\Wizards\OrderWizard;
use Invelity\WizardPackage\Facades\Wizard;
use Livewire\Component;

class OrderForm extends Component
{
    public string $step = 'calculator';

    public array $form = [];

    public function mount(): void
    {
        $this->step = $this->wizard()->current()->id();
        $this->form = $this->wizard()->data($this->step);
    }

    public function submit(): void
    {
        $this->wizard()->process($this->step, $this->form);   // validation errors appear in $errors by field name

        $this->step = $this->wizard()->current()->id();
        $this->form = $this->wizard()->data($this->step);
    }

    public function render()
    {
        return view('livewire.order-form', [
            'navigation' => $this->wizard()->navigation($this->step),
            'progress' => $this->wizard()->progress(),
        ]);
    }

    private function wizard(): OrderWizard
    {
        return Wizard::for(OrderWizard::class);   // resolve per request; never store it on the component
    }
}
```

## Inertia

```php
use Invelity\WizardPackage\Http\Resources\WizardResource;

public function show(OrderWizard $wizard, string $step)
{
    return Inertia::render("Order/{$step}", [
        'wizard' => new WizardResource($wizard, $step),
    ]);
}
```

`Invelity\WizardPackage\Http\Resources\WizardResource` is the same shape the JSON API returns, so the page props match the
[response format]({{ site.baseurl }}/api-reference#http-api).

## Vue, React or a mobile app over the JSON API

```php
Route::middleware('web')->group(fn () => Route::wizard('api/order', OrderWizard::class));
```

```js
async function call(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : undefined,
    });

    if (response.status === 422) {
        return { errors: (await response.json()).errors };
    }

    return response.status === 204 ? {} : { wizard: (await response.json()).data };
}

const { wizard } = await call('GET', '/api/order');                         // where am I?
const result = await call('POST', `/api/order/${wizard.step.id}`, form);    // submit the step
if (result.errors) showErrors(result.errors); else render(result.wizard);   // wizard.step is the next step
await call('POST', '/api/order/newsletter/skip');                           // skip an optional step
await call('POST', '/api/order');                                           // complete
```

`wizard.navigation` gives everything a progress bar or step list needs: `title`, `status`, `is_current`,
`is_accessible`, `url`.

## A checkout that follows the user across devices

```php
class CheckoutWizard extends Wizard
{
    protected ?string $store = 'database';   // keyed by the authenticated user by default

    protected array $steps = [ShippingStep::class, PaymentStep::class, ReviewStep::class, ThankYouStep::class];
}
```

```php
// Resume where the user left off, on any device
return redirect($wizard->url($wizard->firstUnfinished()?->id() ?? $wizard->current()->id()));
```
