<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use WebSystem\WizardPackage\Contracts\WizardManagerInterface;

class WizardCompletionController extends Controller
{
    public function __construct(
        private readonly WizardManagerInterface $manager,
    ) {}

    public function __invoke(string $wizard): JsonResponse
    {
        $this->manager->initialize($wizard);

        $result = $this->manager->complete();

        if (! $result->success) {
            return response()->json([
                'success' => false,
                'errors' => $result->errors,
                'message' => $result->message ?? 'Wizard cannot be completed',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result->data,
            'message' => $result->message ?? 'Wizard completed successfully',
        ]);
    }
}
