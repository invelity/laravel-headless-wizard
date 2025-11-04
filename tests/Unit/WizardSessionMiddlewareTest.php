<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Invelity\WizardPackage\Http\Middleware\WizardSession;

test('middleware passes through when session is available', function () {
    $middleware = new WizardSession();
    $request = Request::create('/test', 'GET');
    $request->setLaravelSession($this->app->make('session.store'));
    
    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });
    
    expect($response->getStatusCode())->toBe(200);
});

test('middleware throws exception when session is not available', function () {
    $middleware = new WizardSession();
    $request = new Request();
    
    $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });
})->throws(\RuntimeException::class);

test('middleware starts session', function () {
    $middleware = new WizardSession();
    $request = Request::create('/test', 'GET');
    $request->setLaravelSession($this->app->make('session.store'));
    
    $middleware->handle($request, function ($req) {
        expect($req->hasSession())->toBeTrue();
        return response('OK', 200);
    });
});
