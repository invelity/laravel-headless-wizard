<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Session\Store as SessionStore;
use Invelity\WizardPackage\StoreManager;

it('reads the session key written by 1.x', function () {
    session()->put('wizard_order-wizard', [
        'current_step' => 'summary',
        'completed_steps' => ['calculator'],
        'packages' => [['weight' => 2]],
    ]);

    expect(app(StoreManager::class)->store('session')->get('order-wizard', 'ignored'))->toBe([
        'current_step' => 'summary',
        'completed_steps' => ['calculator'],
        'packages' => [['weight' => 2]],
    ]);
});

it('uses the configured prefix', function () {
    config(['wizard.stores.session.prefix' => 'flows.']);

    app(StoreManager::class)->store('session')->put('order', 'ignored', ['current_step' => 'summary']);

    expect(session()->get('flows.order'))->toBe(['current_step' => 'summary']);
});

it('uses the session of the current request', function () {
    $store = app(StoreManager::class)->store('session');

    $first = tap(new SessionStore('first', app('session')->getHandler()))->start();
    $second = tap(new SessionStore('second', app('session')->getHandler()))->start();

    $request = Request::create('/');
    $request->setLaravelSession($first);
    $this->app->instance('request', $request);
    $store->put('order', 'ignored', ['current_step' => 'calculator']);

    $request = Request::create('/');
    $request->setLaravelSession($second);
    $this->app->instance('request', $request);

    expect($store->get('order', 'ignored'))->toBeNull()
        ->and($first->get('wizard_order'))->toBe(['current_step' => 'calculator']);
});
