<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;

beforeEach(function () {
    Route::middleware('web')->group(fn () => Route::wizard('order', OrderWizard::class));
});

/**
 * Finish the required steps of the order wizard over HTTP.
 */
function finishOrderOverHttp(): void
{
    test()->postJson('/order/calculator', ['weight' => '2'])->assertOk();
    test()->postJson('/order/personal-data', ['name' => 'Jane', 'email' => 'jane@example.com'])->assertOk();
    test()->postJson('/order/newsletter/skip')->assertOk();
    test()->postJson('/order/billing')->assertOk();
    test()->postJson('/order/summary', ['terms' => 'yes'])->assertOk();
}

it('shows the wizard from the step the visitor is on', function () {
    $this->getJson('/order')
        ->assertOk()
        ->assertJsonPath('data.wizard', 'order')
        ->assertJsonPath('data.step', [
            'id' => 'calculator',
            'title' => 'Calculator',
            'is_optional' => false,
            'is_display_only' => false,
            'data' => [],
        ])
        ->assertJsonPath('data.previous_step', null)
        ->assertJsonPath('data.next_step', 'personal-data')
        ->assertJsonPath('data.is_completed', false)
        ->assertJsonPath('data.progress', ['completed_steps' => 0, 'total_steps' => 5, 'percentage' => 0])
        ->assertJsonPath('data.navigation.0', [
            'id' => 'calculator',
            'title' => 'Calculator',
            'position' => 1,
            'status' => 'pending',
            'is_current' => true,
            'is_accessible' => true,
            'is_optional' => false,
            'is_display_only' => false,
            'url' => 'http://localhost/order/calculator',
        ])
        ->assertJsonCount(6, 'data.navigation');

    expect(session()->has('wizard_order'))->toBeFalse();
});

it('serialises empty step data as an object', function () {
    expect($this->getJson('/order')->getContent())->toContain('"data":{}');
});

it('processes a step and moves to the next one', function () {
    $this->postJson('/order/calculator', ['weight' => '2,5'])
        ->assertOk()
        ->assertJsonPath('data.step.id', 'personal-data')
        ->assertJsonPath('data.previous_step', 'calculator')
        ->assertJsonPath('data.next_step', 'newsletter')
        ->assertJsonPath('data.progress.percentage', 20)
        ->assertJsonPath('data.navigation.0.status', 'completed');
});

it('shows a step with its stored data', function () {
    $this->postJson('/order/calculator', ['weight' => '2']);

    $this->getJson('/order/calculator')
        ->assertOk()
        ->assertJsonPath('data.step.id', 'calculator')
        ->assertJsonPath('data.step.data', ['weight' => '2', 'price' => 9])
        ->assertJsonPath('data.next_step', 'personal-data');
});

it('refuses steps the visitor may not open yet', function () {
    $this->getJson('/order/summary')->assertForbidden();
    $this->postJson('/order/summary', ['terms' => 'yes'])->assertForbidden();
});

it('returns validation errors', function () {
    $this->postJson('/order/calculator', ['weight' => 'heavy'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('weight');
});

it('skips optional steps', function () {
    $this->postJson('/order/calculator', ['weight' => '2']);
    $this->postJson('/order/personal-data', ['name' => 'Jane', 'email' => 'jane@example.com']);

    $this->postJson('/order/newsletter/skip')
        ->assertOk()
        ->assertJsonPath('data.step.id', 'billing')
        ->assertJsonPath('data.navigation.2.status', 'skipped');
});

it('only routes the steps each action applies to', function () {
    $this->postJson('/order/calculator/skip')->assertNotFound();
    $this->postJson('/order/confirmation')->assertMethodNotAllowed();
    $this->getJson('/order/payment')->assertNotFound();
});

it('completes the wizard', function () {
    finishOrderOverHttp();

    $this->postJson('/order')
        ->assertOk()
        ->assertJsonPath('data.is_completed', true)
        ->assertJsonPath('data.step.id', 'confirmation')
        ->assertJsonPath('data.step.is_display_only', true);

    $this->postJson('/order')->assertConflict();
    $this->postJson('/order/calculator', ['weight' => '3'])->assertConflict();
});

it('refuses to complete an unfinished wizard', function () {
    $this->postJson('/order')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('steps');
});

it('resets the wizard', function () {
    $this->postJson('/order/calculator', ['weight' => '2']);

    $this->deleteJson('/order')->assertNoContent();

    $this->getJson('/order')
        ->assertJsonPath('data.step.id', 'calculator')
        ->assertJsonPath('data.progress.completed_steps', 0);
});

it('keeps visitors apart', function () {
    $this->postJson('/order/calculator', ['weight' => '2']);

    $this->flushSession();

    $this->getJson('/order')->assertJsonPath('data.step.id', 'calculator');
    $this->getJson('/order/personal-data')->assertForbidden();
});

it('redirects HTML forms back with errors', function () {
    $this->from('/order/calculator')
        ->post('/order/calculator', ['weight' => 'heavy'])
        ->assertRedirect('/order/calculator')
        ->assertSessionHasErrors('weight');
});
