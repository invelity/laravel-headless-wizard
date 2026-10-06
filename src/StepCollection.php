<?php

declare(strict_types=1);

namespace Invelity\WizardPackage;

use Illuminate\Support\Collection;
use Invelity\WizardPackage\Contracts\Step;
use Invelity\WizardPackage\Exceptions\StepNotFoundException;

/**
 * The ordered steps of a wizard.
 *
 * @extends Collection<int, Step>
 */
final class StepCollection extends Collection
{
    /**
     * Find a step by its identifier.
     */
    public function find(string $id): ?Step
    {
        return $this->first(fn (Step $step): bool => $step->id() === $id);
    }

    /**
     * Find a step by its identifier or throw an exception.
     *
     * @throws StepNotFoundException
     */
    public function findOrFail(string $id): Step
    {
        return $this->find($id) ?? throw new StepNotFoundException($id);
    }

    /**
     * Find the step that is an instance of the given class.
     *
     * @param  class-string<Step>  $class
     */
    public function findByClass(string $class): ?Step
    {
        return $this->first(fn (Step $step): bool => $step instanceof $class);
    }

    /**
     * Get the position of the given step, starting at zero.
     */
    public function position(string $id): ?int
    {
        foreach ($this->values() as $position => $step) {
            if ($step->id() === $id) {
                return $position;
            }
        }

        return null;
    }

    /**
     * Get the identifiers of the steps, in order.
     *
     * @return list<string>
     */
    public function ids(): array
    {
        return array_values(array_map(fn (Step $step): string => $step->id(), $this->all()));
    }
}
