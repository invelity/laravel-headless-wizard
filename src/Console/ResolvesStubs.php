<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Console;

trait ResolvesStubs
{
    /**
     * Resolve the fully-qualified path to the stub, preferring a published copy.
     *
     * Published stubs live in the application's "stubs" directory, like Laravel's own.
     */
    protected function resolveStubPath(string $stub): string
    {
        $published = $this->laravel->basePath(trim($stub, '/'));

        return file_exists($published) ? $published : dirname(__DIR__, 2).'/resources'.$stub;
    }
}
