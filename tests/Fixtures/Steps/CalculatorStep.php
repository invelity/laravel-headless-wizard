<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures\Steps;

use Invelity\WizardPackage\Step;
use Invelity\WizardPackage\Tests\Fixtures\PriceCalculator;
use Invelity\WizardPackage\Tests\Fixtures\Requests\CalculatorRequest;

final class CalculatorStep extends Step
{
    protected ?string $formRequest = CalculatorRequest::class;

    /**
     * Add the quoted price to the validated data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handle(array $data, PriceCalculator $prices): array
    {
        return [...$data, 'price' => $prices->quote((float) $data['weight'])];
    }
}
