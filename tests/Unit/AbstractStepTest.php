<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Unit;

use Invelity\WizardPackage\Steps\AbstractStep;
use Invelity\WizardPackage\Tests\TestCase;

class AbstractStepTest extends TestCase
{
    public function test_render_method_does_not_exist(): void
    {
        $reflection = new \ReflectionClass(AbstractStep::class);

        $this->assertFalse(
            $reflection->hasMethod('render'),
            'AbstractStep should not have render() method in headless architecture'
        );
    }

    public function test_get_view_name_method_does_not_exist(): void
    {
        $reflection = new \ReflectionClass(AbstractStep::class);

        $this->assertFalse(
            $reflection->hasMethod('getViewName'),
            'AbstractStep should not have getViewName() method in headless architecture'
        );
    }

    public function test_steps_do_not_return_views(): void
    {
        $stepFiles = glob(__DIR__.'/../../src/Steps/*.php');

        foreach ($stepFiles as $file) {
            $className = 'Invelity\\WizardPackage\\Steps\\'.basename($file, '.php');

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);

            if ($reflection->isAbstract()) {
                continue;
            }

            foreach ($reflection->getMethods() as $method) {
                if ($method->class !== $className) {
                    continue;
                }

                $returnType = $method->getReturnType();

                if ($returnType instanceof \ReflectionNamedType) {
                    $returnTypeName = $returnType->getName();
                    $this->assertNotEquals('Illuminate\View\View', $returnTypeName, "$className::{$method->name}() should not return View");
                    $this->assertNotEquals('Illuminate\Contracts\View\View', $returnTypeName, "$className::{$method->name}() should not return View");
                }
            }
        }
    }
}
