<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Commands\Concerns;

use Illuminate\Support\Facades\File;
use RuntimeException;

trait WritesConfig
{
    protected function writeConfigSafely(string $configPath, callable $modifier): void
    {
        if (! File::exists($configPath)) {
            throw new RuntimeException(__('Config file not found'));
        }

        $backupPath = $configPath.'.backup';
        File::copy($configPath, $backupPath);

        try {
            $config = require $configPath;
            $config = $modifier($config);

            $content = $this->buildConfigContent($config);

            $this->writeWithLock($configPath, $content);

            File::delete($backupPath);
        } catch (\Exception $e) {
            if (File::exists($backupPath)) {
                File::move($backupPath, $configPath);
            }

            throw $e;
        }
    }

    protected function buildConfigContent(array $config): string
    {
        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($config, true).";\n";

        return str_replace("'App\\\\\\\\Wizards", "'App\\\\Wizards", $content);
    }

    protected function writeWithLock(string $path, string $content): void
    {
        $handle = fopen($path, 'w');

        if (! $handle) {
            throw new RuntimeException(__('Failed to open file: {path}', ['path' => $path]));
        }

        try {
            if (flock($handle, LOCK_EX)) {
                fwrite($handle, $content);
                flock($handle, LOCK_UN);
            } else {
                throw new RuntimeException(__('Failed to acquire lock on: {path}', ['path' => $path]));
            }
        } finally {
            fclose($handle);
        }
    }
}
