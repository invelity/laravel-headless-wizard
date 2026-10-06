<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Contracts;

interface Store
{
    /**
     * Get the stored state of a wizard for a scope.
     *
     * The scope identifies the visitor. Stores that are private to one visitor, such as the
     * session, may ignore it.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $wizard, string $scope): ?array;

    /**
     * Store the state of a wizard for a scope.
     *
     * @param  array<string, mixed>  $state
     */
    public function put(string $wizard, string $scope, array $state): void;

    /**
     * Remove the stored state of a wizard for a scope.
     */
    public function forget(string $wizard, string $scope): void;
}
