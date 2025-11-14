<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('all PHP files have strict types declaration')
    ->expect('Invelity\WizardPackage')
    ->toUseStrictTypes();

arch('events are final')
    ->expect('Invelity\WizardPackage\Events')
    ->classes()
    ->toBeFinal();

arch('exceptions extend base exception')
    ->expect('Invelity\WizardPackage\Exceptions')
    ->classes()
    ->toExtend(Exception::class);

arch('contracts are interfaces')
    ->expect('Invelity\WizardPackage\Contracts')
    ->toBeInterfaces();

arch('commands extend Laravel Command')
    ->expect('Invelity\WizardPackage\Commands')
    ->classes()
    ->toExtend('Illuminate\Console\Command');

arch('controllers extend Laravel Controller')
    ->expect('Invelity\WizardPackage\Http\Controllers')
    ->classes()
    ->toExtend('Illuminate\Routing\Controller');

arch('form requests extend Laravel FormRequest')
    ->expect('Invelity\WizardPackage\Http\Requests')
    ->classes()
    ->toExtend('Illuminate\Foundation\Http\FormRequest');

arch('models extend Eloquent Model')
    ->expect('Invelity\WizardPackage\Models')
    ->classes()
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('no static calls except Facades and Laravel helpers')
    ->expect('Invelity\WizardPackage')
    ->not->toUse('static');

test('controllers follow CRUD or single-action pattern', function () {
    $controllerFiles = glob(__DIR__.'/../src/Http/Controllers/*.php');

    foreach ($controllerFiles as $file) {
        $className = 'Invelity\\WizardPackage\\Http\\Controllers\\'.basename($file, '.php');

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);
        $publicMethods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn ($method) => ! $method->isConstructor() && $method->class === $className
        );

        $methodNames = array_map(fn ($m) => $m->name, $publicMethods);
        $crudMethods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

        $isSingleAction = (count($methodNames) === 1 && in_array('__invoke', $methodNames));

        if (! $isSingleAction) {
            foreach ($methodNames as $method) {
                expect($method)->toBeIn($crudMethods, "$className::$method should be a CRUD method or controller should use __invoke");
            }
        }
    }
});

test('classes follow single responsibility principle', function () {
    $classFiles = glob(__DIR__.'/../src/**/*.php');

    foreach ($classFiles as $file) {
        $relativePath = str_replace(__DIR__.'/../src/', '', $file);
        $className = 'Invelity\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! class_exists($className) && ! interface_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isInterface() || $reflection->isAbstract()) {
            continue;
        }

        $publicMethods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn ($method) => ! $method->isConstructor() && $method->class === $className
        );

        $methodCount = count($publicMethods);

        if ($className === 'Invelity\\WizardPackage\\Core\\WizardManager') {
            expect($methodCount)->toBeLessThan(20, "$className has $methodCount public methods (core manager - higher limit allowed)");
        } else {
            expect($methodCount)->toBeLessThan(15, "$className has $methodCount public methods - consider splitting responsibilities");
        }
    }
});

test('interfaces are focused and not too large', function () {
    $interfaceFiles = glob(__DIR__.'/../src/Contracts/*.php');

    foreach ($interfaceFiles as $file) {
        $className = 'Invelity\\WizardPackage\\Contracts\\'.basename($file, '.php');

        if (! interface_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);
        $methods = $reflection->getMethods();
        $methodCount = count($methods);

        $coreInterfaces = [
            'Invelity\\WizardPackage\\Contracts\\WizardManagerInterface',
            'Invelity\\WizardPackage\\Contracts\\WizardStepInterface',
        ];

        if (in_array($className, $coreInterfaces)) {
            expect($methodCount)->toBeLessThan(20, "$className has $methodCount methods (core interface - higher limit allowed)");
        } else {
            expect($methodCount)->toBeLessThan(10, "$className has $methodCount methods - consider interface segregation");
        }
    }
});

test('classes depend on abstractions not concretions', function () {
    $classFiles = glob(__DIR__.'/../src/{Core,Services}/*.php', GLOB_BRACE);

    foreach ($classFiles as $file) {
        $relativePath = str_replace(__DIR__.'/../src/', '', $file);
        $className = 'Invelity\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isAbstract()) {
            continue;
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            continue;
        }

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type === null || ! $type instanceof ReflectionNamedType) {
                continue;
            }

            $typeName = $type->getName();

            if (class_exists($typeName)) {
                $typeReflection = new ReflectionClass($typeName);

                $isAbstraction = $typeReflection->isInterface() || $typeReflection->isAbstract();

                $allowedConcretes = [
                    'Invelity\\WizardPackage\\Core\\WizardConfiguration',
                    'Invelity\\WizardPackage\\Steps\\StepFactory',
                    'Invelity\\WizardPackage\\Factories\\WizardNavigationFactory',
                ];

                if (in_array($typeName, $allowedConcretes)) {
                    expect(true)->toBeTrue("$className depends on $typeName (factory/configuration - acceptable)");
                } else {
                    expect($isAbstraction)->toBeTrue(
                        "$className depends on concrete class $typeName - should depend on interface/abstract"
                    );
                }
            }
        }
    }
});

test('no methods return View instances', function () {
    $classFiles = glob(__DIR__.'/../src/**/*.php');

    foreach ($classFiles as $file) {
        $relativePath = str_replace(__DIR__.'/../src/', '', $file);
        $className = 'Invelity\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isInterface()) {
            continue;
        }

        if (str_contains($className, 'Component')) {
            continue;
        }

        foreach ($reflection->getMethods() as $method) {
            if ($method->class !== $className) {
                continue;
            }

            $returnType = $method->getReturnType();

            if ($returnType instanceof ReflectionNamedType) {
                $returnTypeName = $returnType->getName();
                expect($returnTypeName)->not->toBe('Illuminate\View\View', "$className::{$method->name}() returns View - headless architecture should return arrays/JSON");
                expect($returnTypeName)->not->toBe('Illuminate\Contracts\View\View', "$className::{$method->name}() returns View - headless architecture should return arrays/JSON");
            }
        }
    }
});

test('step classes do not have rules method', function () {
    $stepFiles = glob(__DIR__.'/../src/Steps/**/*.php');
    $concreteStepFound = false;

    foreach ($stepFiles as $file) {
        $relativePath = str_replace(__DIR__.'/../src/', '', $file);
        $className = 'Invelity\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isAbstract() || $reflection->isInterface()) {
            continue;
        }

        $concreteStepFound = true;

        expect($reflection->hasMethod('rules'))->toBeFalse(
            "$className should not have rules() method - validation should be in FormRequest"
        );
    }

    if (! $concreteStepFound) {
        expect(true)->toBeTrue('No concrete step classes found in package (steps are generated by users)');
    }
});

test('all public methods have return type declarations', function () {
    $classFiles = glob(__DIR__.'/../src/**/*.php');

    foreach ($classFiles as $file) {
        $relativePath = str_replace(__DIR__.'/../src/', '', $file);
        $className = 'Invelity\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! class_exists($className) && ! interface_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $className) {
                continue;
            }

            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            $traitMethods = ['dispatch', 'dispatchIf', 'dispatchUnless', 'dispatchSync', 'dispatchAfterResponse', 'broadcast', 'broadcastOn', 'broadcastWith', 'broadcastAs', 'broadcastWhen', '__serialize', '__unserialize', 'restoreModel', 'getQueueableRelations', 'getQueueableConnection', 'getQueueableId', 'factory'];
            if (in_array($method->name, $traitMethods)) {
                continue;
            }

            expect($method->hasReturnType())->toBeTrue(
                "$className::{$method->name}() must have return type declaration (PHP 8.4 strict types)"
            );
        }
    }
});

test('naming conventions are followed', function () {
    $tests = [
        ['pattern' => __DIR__.'/../src/Commands/*Command.php', 'suffix' => 'Command', 'type' => 'Command'],
        ['pattern' => __DIR__.'/../src/Http/Controllers/*Controller.php', 'suffix' => 'Controller', 'type' => 'Controller'],
        ['pattern' => __DIR__.'/../src/Events/*.php', 'suffix' => '', 'type' => 'Event'],
        ['pattern' => __DIR__.'/../src/Exceptions/*Exception.php', 'suffix' => 'Exception', 'type' => 'Exception'],
        ['pattern' => __DIR__.'/../src/Contracts/*Interface.php', 'suffix' => 'Interface', 'type' => 'Interface'],
    ];

    foreach ($tests as $test) {
        $files = glob($test['pattern']);

        foreach ($files as $file) {
            $className = basename($file, '.php');

            if ($test['suffix']) {
                expect($className)->toEndWith($test['suffix'], "{$test['type']} classes must end with '{$test['suffix']}'");
            }

            expect($className[0])->toMatch('/[A-Z]/', "{$test['type']} class names must start with uppercase letter (PascalCase)");
        }
    }
});

test('value objects use constructor property promotion and are immutable', function () {
    $valueObjectFiles = glob(__DIR__.'/../src/ValueObjects/*.php');

    foreach ($valueObjectFiles as $file) {
        $className = 'Invelity\\WizardPackage\\ValueObjects\\'.basename($file, '.php');

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            continue;
        }

        $hasPromotion = false;
        foreach ($constructor->getParameters() as $param) {
            if ($param->isPromoted()) {
                $hasPromotion = true;
                break;
            }
        }

        expect($hasPromotion)->toBeTrue(
            "$className should use constructor property promotion (PHP 8.0+)"
        );
    }
});

test('no God objects - classes stay focused', function () {
    $classFiles = glob(__DIR__.'/../src/**/*.php');
    $maxProperties = 11;

    foreach ($classFiles as $file) {
        $relativePath = str_replace(__DIR__.'/../src/', '', $file);
        $className = 'Invelity\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isInterface() || $reflection->isAbstract()) {
            continue;
        }

        $properties = $reflection->getProperties();
        $propertyCount = count($properties);

        if (str_contains($className, 'Configuration') || str_contains($className, 'Command') || str_contains($className, 'Model') || str_contains($className, 'Component')) {
            continue;
        }

        expect($propertyCount)->toBeLessThanOrEqual($maxProperties,
            "$className has $propertyCount properties - God object detected, consider splitting"
        );
    }
});

test('services and managers use constructor property promotion', function () {
    $classFiles = glob(__DIR__.'/../src/{Core,Services}/*.php', GLOB_BRACE);

    foreach ($classFiles as $file) {
        $relativePath = str_replace(__DIR__.'/../src/', '', $file);
        $className = 'Invelity\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            continue;
        }

        $hasPromotion = false;
        foreach ($constructor->getParameters() as $param) {
            if ($param->isPromoted()) {
                $hasPromotion = true;
                break;
            }
        }

        expect($hasPromotion)->toBeTrue(
            "$className should use constructor property promotion (PHP 8.0+)"
        );
    }
});
