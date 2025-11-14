<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Session\ArraySessionHandler;
use Invelity\WizardPackage\Http\Middleware\WizardSession;

test('middleware passes through when session is available', function () {
    $middleware = new WizardSession;
    $request = Request::create('/test', 'GET');
    
    $session = new Store('test-session', new ArraySessionHandler(60));
    $session->setId('test-session-id');
    $request->setLaravelSession($session);

    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('middleware throws exception when session is not available', function () {
    $middleware = new WizardSession;
    $request = new Request;

    $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });
})->throws(\RuntimeException::class);

test('middleware starts session', function () {
    $middleware = new WizardSession;
    $request = Request::create('/test', 'GET');
    
    $session = new Store('test-session', new ArraySessionHandler(60));
    $session->setId('test-session-id');
    $request->setLaravelSession($session);

    $middleware->handle($request, function ($req) {
        expect($req->hasSession())->toBeTrue();

        return response('OK', 200);
    });
});
