<?php

declare(strict_types=1);

use Invelity\WizardPackage\Steps\AbstractStep;

test('render method does not exist', function () {
    $reflection = new ReflectionClass(AbstractStep::class);

    expect($reflection->hasMethod('render'))->toBeFalse();
});

test('get view name method does not exist', function () {
    $reflection = new ReflectionClass(AbstractStep::class);

    expect($reflection->hasMethod('getViewName'))->toBeFalse();
});

test('steps do not return views', function () {
    $stepFiles = glob(__DIR__.'/../../src/Steps/*.php');

    expect($stepFiles)->not->toBeEmpty();

    foreach ($stepFiles as $file) {
        $className = 'Invelity\\WizardPackage\\Steps\\'.basename($file, '.php');

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isAbstract()) {
            continue;
        }

        foreach ($reflection->getMethods() as $method) {
            if ($method->class !== $className) {
                continue;
            }

            $returnType = $method->getReturnType();

            if ($returnType === null) {
                continue;
            }

            if ($returnType instanceof ReflectionNamedType) {
                $returnTypeName = $returnType->getName();
                expect($returnTypeName)->not->toBe('Illuminate\View\View');
                expect($returnTypeName)->not->toBe('Illuminate\Contracts\View\View');
            }
        }
    }
});
