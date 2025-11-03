<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

test('controllers follow CRUD or single-action pattern', function () {
    $controllerFiles = glob(__DIR__.'/../src/Http/Controllers/*.php');

    foreach ($controllerFiles as $file) {
        $className = 'WebSystem\\WizardPackage\\Http\\Controllers\\'.basename($file, '.php');

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
        $className = 'WebSystem\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

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

        if ($className === 'WebSystem\\WizardPackage\\Core\\WizardManager') {
            expect($methodCount)->toBeLessThan(20, "$className has $methodCount public methods (core manager - higher limit allowed)");
        } else {
            expect($methodCount)->toBeLessThan(15, "$className has $methodCount public methods - consider splitting responsibilities");
        }
    }
});

test('interfaces are focused and not too large', function () {
    $interfaceFiles = glob(__DIR__.'/../src/Contracts/*.php');

    foreach ($interfaceFiles as $file) {
        $className = 'WebSystem\\WizardPackage\\Contracts\\'.basename($file, '.php');

        if (! interface_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);
        $methods = $reflection->getMethods();
        $methodCount = count($methods);

        $coreInterfaces = [
            'WebSystem\\WizardPackage\\Contracts\\WizardManagerInterface',
            'WebSystem\\WizardPackage\\Contracts\\WizardStepInterface',
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
        $className = 'WebSystem\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

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
                    'WebSystem\\WizardPackage\\Core\\WizardConfiguration',
                    'WebSystem\\WizardPackage\\Steps\\StepFactory',
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
        $className = 'WebSystem\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isInterface()) {
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
        $className = 'WebSystem\\WizardPackage\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

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
