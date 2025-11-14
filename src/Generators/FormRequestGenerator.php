<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Generators;

use Illuminate\Support\Facades\File;

class FormRequestGenerator
{
    public function generate(string $stepClass): void
    {
        $directory = app_path('Http/Requests/Wizards');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/request.php.stub');

        $requestClass = str_replace('Step', '', $stepClass).'Request';

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            ['App\\Http\\Requests\\Wizards', $requestClass],
            $stub
        );

        File::put(app_path("Http/Requests/Wizards/{$requestClass}.php"), $content);
    }
}
