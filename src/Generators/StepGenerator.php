<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Generators;

use Illuminate\Support\Facades\File;

class StepGenerator
{
    public function generate(
        string $wizardName,
        string $stepClass,
        string $stepId,
        string $title,
        int $order,
        bool $optional
    ): void {
        $directory = app_path("Wizards/{$wizardName}Wizard/Steps");

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/step.php.stub');

        $requestClass = str_replace('Step', '', $stepClass).'Request';

        $optionalParams = $optional ? ',\n            isOptional: true,\n            canSkip: true' : '';

        $content = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ stepId }}',
                '{{ title }}',
                '{{ order }}',
                '{{ optionalParams }}',
                '{{ formRequestNamespace }}',
                '{{ formRequestClass }}',
            ],
            [
                "App\\Wizards\\{$wizardName}Wizard\\Steps",
                $stepClass,
                $stepId,
                $title,
                (string) $order,
                $optionalParams,
                'App\\Http\\Requests\\Wizards',
                $requestClass,
            ],
            $stub
        );

        File::put(app_path("Wizards/{$wizardName}Wizard/Steps/{$stepClass}.php"), $content);
    }

    public function exists(string $stepClass, string $wizardName): bool
    {
        return File::exists(app_path("Wizards/{$wizardName}Wizard/Steps/{$stepClass}.php"));
    }

    public function getLastStepOrder(string $wizardName): int
    {
        $stepsPath = app_path("Wizards/{$wizardName}Wizard/Steps");

        if (! File::isDirectory($stepsPath)) {
            return 0;
        }

        $files = File::files($stepsPath);

        return count($files);
    }

    public function reorderExistingSteps(string $wizardName, int $newStepOrder): void
    {
        $stepsPath = app_path("{$wizardName}Wizard/Steps");

        if (! File::isDirectory($stepsPath)) {
            return;
        }

        $files = File::files($stepsPath);

        foreach ($files as $file) {
            $content = File::get($file->getPathname());

            if (preg_match('/order:\s*(\d+)/', $content, $matches)) {
                $currentOrder = (int) $matches[1];

                if ($currentOrder >= $newStepOrder) {
                    $updatedContent = preg_replace(
                        '/order:\s*\d+/',
                        'order: '.($currentOrder + 1),
                        $content
                    );

                    File::put($file->getPathname(), $updatedContent);
                }
            }
        }
    }
}
