<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Invelity\WizardPackage\Steps\AbstractStep;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

readonly class WizardDiscoveryService
{
    public function __construct(
        private Cache $cache,
        private LoggerInterface $logger
    ) {}

    public function discoverWizards(): Collection
    {
        return $this->cache->remember(
            'wizard.discovered_wizards',
            now()->addDay(),
            fn () => $this->scanWizardDirectories()
        );
    }

    public function discoverSteps(string $wizardClass): Collection
    {
        $cacheKey = "wizard.steps.{$wizardClass}";

        return $this->cache->remember(
            $cacheKey,
            now()->addDay(),
            fn () => $this->scanStepDirectories($wizardClass)
        );
    }

    public function clearCache(): void
    {
        $this->cache->forget('wizard.discovered_wizards');
    }

    public function warmCache(): void
    {
        $wizards = $this->discoverWizards();

        $wizards->each(function ($wizard) {
            $wizardClass = is_object($wizard) ? get_class($wizard) : $wizard['class'] ?? null;

            if ($wizardClass) {
                $this->discoverSteps($wizardClass);
            }
        });
    }

    private function scanWizardDirectories(): Collection
    {
        $wizardPath = app_path('Wizards');

        if (! File::isDirectory($wizardPath)) {
            return collect();
        }

        $directories = File::directories($wizardPath);

        return collect($directories)
            ->map(fn ($dir) => $this->instantiateWizardClass($dir))
            ->filter()
            ->values();
    }

    private function scanStepDirectories(string $wizardClass): Collection
    {
        try {
            $reflection = new ReflectionClass($wizardClass);
            $wizardDir = dirname($reflection->getFileName());
            $stepsDir = $wizardDir.'/Steps';

            if (! File::isDirectory($stepsDir)) {
                return collect();
            }

            $files = File::files($stepsDir);

            return collect($files)
                ->filter(fn ($file) => $file->getExtension() === 'php')
                ->map(fn ($file) => $this->instantiateStepClass($file, dirname($wizardDir)))
                ->filter()
                ->sortBy('order')
                ->values();
        } catch (Throwable $e) {
            $this->logger->warning("Failed to scan step directories for {$wizardClass}", [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    private function instantiateWizardClass(string $wizardDir): ?object
    {
        $wizardFolderName = basename($wizardDir);
        $wizardClassName = str_replace('Wizard', '', $wizardFolderName);
        $wizardFile = $wizardDir.'/'.$wizardClassName.'.php';

        if (! File::exists($wizardFile)) {
            return null;
        }

        $className = "App\\Wizards\\{$wizardFolderName}\\{$wizardClassName}";

        if (! class_exists($className)) {
            return null;
        }

        try {
            return app($className);
        } catch (Throwable $e) {
            $this->logger->warning("Failed to instantiate wizard class: {$className}", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function instantiateStepClass(\SplFileInfo $file, string $wizardsBasePath): ?object
    {
        $className = $this->filePathToClassName($file, $wizardsBasePath);

        if (! class_exists($className) || ! is_subclass_of($className, AbstractStep::class)) {
            return null;
        }

        try {
            return app($className);
        } catch (Throwable $e) {
            $this->logger->warning("Failed to instantiate step class: {$className}", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function filePathToClassName(\SplFileInfo $file, string $wizardsBasePath): string
    {
        $path = $file->getPathname();
        $relativePath = Str::after($path, $wizardsBasePath.'/');
        $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);

        return 'App\\Wizards\\'.$classPath;
    }
}
