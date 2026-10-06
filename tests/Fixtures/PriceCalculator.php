<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Tests\Fixtures;

final class PriceCalculator
{
    /**
     * Quote the price of a parcel of the given weight.
     */
    public function quote(float $weight): float
    {
        return round($weight * 4.5, 2);
    }
}
