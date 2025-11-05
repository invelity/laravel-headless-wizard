<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Services\Validation;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Invelity\WizardPackage\Contracts\FormRequestResolverInterface;
use Invelity\WizardPackage\Contracts\WizardStepInterface;

final readonly class FormRequestResolver implements FormRequestResolverInterface
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    /**
     * Resolve FormRequest class for the given step.
     *
     * Strategy:
     * 1. Check config override in wizard.validation.form_requests
     * 2. Use naming convention: PersonalInfoStep → PersonalInfoRequest
     * 3. Return null if FormRequest class doesn't exist
     */
    public function resolveForStep(WizardStepInterface $step): ?string
    {
        $stepClass = $step::class;

        // Strategy 1: Config override
        $override = $this->config->get("wizard.validation.form_requests.{$stepClass}");
        if ($override && class_exists($override)) {
            return $override;
        }

        // Strategy 2: Convention-based discovery
        $formRequestClass = $this->discoverByConvention($stepClass);

        return $formRequestClass && class_exists($formRequestClass)
            ? $formRequestClass
            : null;
    }

    /**
     * Discover FormRequest class using naming convention.
     *
     * Convention: App\Wizards\Steps\PersonalInfoStep
     *          → App\Http\Requests\Wizards\PersonalInfoRequest
     */
    private function discoverByConvention(string $stepClass): string
    {
        // Replace namespace path: \Steps\ → \Http\Requests\Wizards\
        $formRequestClass = str_replace('\\Steps\\', '\\Http\\Requests\\Wizards\\', $stepClass);

        // Replace class suffix: Step → Request
        return preg_replace('/Step$/', 'Request', $formRequestClass) ?? $formRequestClass;
    }
}
