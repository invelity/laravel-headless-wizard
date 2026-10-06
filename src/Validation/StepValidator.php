<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Validation;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Invelity\WizardPackage\Contracts\Step;

/**
 * Validates the input of a step exactly like Laravel validates a type-hinted form request.
 */
final readonly class StepValidator
{
    /**
     * Create a new step validator.
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Validate the input of a step with its form request.
     *
     * The form request runs its whole lifecycle: prepareForValidation(), authorize(), the
     * validator with its after() hooks, and passedValidation(). A step without a form request
     * accepts no input.
     *
     * @param  Request|array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function validate(Step $step, Request|array $input): array
    {
        $class = $step->formRequest();

        if ($class === null) {
            return [];
        }

        $request = $class::createFrom($input instanceof Request ? $input : $this->requestFor($input));

        $request->setContainer($this->container)
            ->setRedirector($this->container->make(Redirector::class))
            ->validateResolved();

        /** @var array<string, mixed> */
        return $request->validated();
    }

    /**
     * Create a request that carries the given input and acts for the current visitor.
     *
     * @param  array<string, mixed>  $input
     */
    private function requestFor(array $input): Request
    {
        $current = $this->container->make('request');

        $request = Request::create($current->getUri(), 'POST', $input);

        $request->setUserResolver($current->getUserResolver());
        $request->setRouteResolver($current->getRouteResolver());

        if ($current->hasSession()) {
            $request->setLaravelSession($current->session());
        }

        return $request;
    }
}
