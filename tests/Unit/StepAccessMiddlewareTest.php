<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Invelity\WizardPackage\Http\Middleware\StepAccess;
use Invelity\WizardPackage\Tests\Fixtures\ContactDetailsStep;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

beforeEach(function () {
    config(['wizard.wizards.checkout' => [
        'steps' => [
            PersonalInfoStep::class,
            ContactDetailsStep::class,
        ],
    ]]);
});

test('middleware allows access to first step', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $middleware = new StepAccess($manager);

    $request = Request::create('/wizard/checkout/personal-info', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/wizard/{wizard}/{step}', []);
        $route->bind($request);
        $route->setParameter('wizard', 'checkout');
        $route->setParameter('step', 'personal-info');

        return $route;
    });

    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('middleware blocks access to inaccessible step', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $middleware = new StepAccess($manager);

    $request = Request::create('/wizard/checkout/contact-details', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/wizard/{wizard}/{step}', []);
        $route->bind($request);
        $route->setParameter('wizard', 'checkout');
        $route->setParameter('step', 'contact-details');

        return $route;
    });

    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('personal-info');
});

test('middleware allows access after completing previous step', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');
    $manager->processStep('personal-info', ['name' => 'John Doe']);

    $middleware = new StepAccess($manager);

    $request = Request::create('/wizard/checkout/contact-details', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/wizard/{wizard}/{step}', []);
        $route->bind($request);
        $route->setParameter('wizard', 'checkout');
        $route->setParameter('step', 'contact-details');

        return $route;
    });

    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('middleware passes through requests without wizard parameter', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $middleware = new StepAccess($manager);

    $request = Request::create('/some-other-route', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/some-other-route', []);
        $route->bind($request);

        return $route;
    });

    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('middleware passes through requests without step parameter', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $middleware = new StepAccess($manager);

    $request = Request::create('/wizard/checkout', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/wizard/{wizard}', []);
        $route->bind($request);
        $route->setParameter('wizard', 'checkout');

        return $route;
    });

    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('middleware initializes wizard automatically', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $middleware = new StepAccess($manager);

    $request = Request::create('/wizard/checkout/personal-info', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/wizard/{wizard}/{step}', []);
        $route->bind($request);
        $route->setParameter('wizard', 'checkout');
        $route->setParameter('step', 'personal-info');

        return $route;
    });

    $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($manager->getCurrentStep())->not->toBeNull();
    expect($manager->getCurrentStep()->getId())->toBe('personal-info');
});

test('middleware redirects with error message when blocked', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $middleware = new StepAccess($manager);

    $request = Request::create('/wizard/checkout/contact-details', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/wizard/{wizard}/{step}', []);
        $route->bind($request);
        $route->setParameter('wizard', 'checkout');
        $route->setParameter('step', 'contact-details');

        return $route;
    });

    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(302);
    expect($response->getSession()->has('error'))->toBeTrue();
});

test('middleware uses current step for redirect when available', function () {
    $manager = app(\Invelity\WizardPackage\Contracts\WizardManagerInterface::class);
    $manager->initialize('checkout');

    $middleware = new StepAccess($manager);

    $request = Request::create('/wizard/checkout/contact-details', 'GET');
    $request->setRouteResolver(function () use ($request) {
        $route = new \Illuminate\Routing\Route('GET', '/wizard/{wizard}/{step}', []);
        $route->bind($request);
        $route->setParameter('wizard', 'checkout');
        $route->setParameter('step', 'contact-details');

        return $route;
    });

    $response = $middleware->handle($request, function ($req) {
        return response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('personal-info');
});
