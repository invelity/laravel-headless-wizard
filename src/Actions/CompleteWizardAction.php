<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Actions;

use Illuminate\Http\JsonResponse;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;
use WebSystem\WizardPackage\Http\Responses\WizardJsonResponse;

final readonly class CompleteWizardAction
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function execute(string $wizard): JsonResponse
    {
        $this->manager->initialize($wizard);

        $result = $this->manager->complete();

        if (! $result->success) {
            return WizardJsonResponse::validationError($result->errors);
        }

        return WizardJsonResponse::completed();
    }
}
