<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use Invelity\WizardPackage\Contracts\WizardDataInterface;
use Invelity\WizardPackage\Contracts\WizardInitializationInterface;
use Invelity\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class CompleteWizardAction
{
    public function __construct(
        private readonly WizardInitializationInterface $initialization,
        private readonly WizardDataInterface $data,
    ) {}

    public function execute(string $wizard): JsonResponse
    {
        $this->initialization->initialize($wizard);

        $result = $this->data->complete();

        if (! $result->success) {
            return WizardJsonResponse::validationError($result->errors);
        }

        return WizardJsonResponse::completed();
    }
}
