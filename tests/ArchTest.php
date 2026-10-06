<?php

declare(strict_types=1);

use Invelity\WizardPackage\Contracts\Step as StepContract;
use Invelity\WizardPackage\Contracts\Store;
use Invelity\WizardPackage\Facades\Wizard as WizardFacade;
use Invelity\WizardPackage\Step;
use Invelity\WizardPackage\Wizard;

arch()->preset()->php();

arch()->preset()->security();

arch('every file declares strict types')
    ->expect('Invelity\WizardPackage')
    ->toUseStrictTypes();

arch('contracts are interfaces')
    ->expect('Invelity\WizardPackage\Contracts')
    ->toBeInterfaces();

arch('classes are final unless applications extend them')
    ->expect('Invelity\WizardPackage')
    ->classes()
    ->toBeFinal()
    ->ignoring([Wizard::class, Step::class, WizardFacade::class]);

arch('the base classes are abstract and implement their contracts')
    ->expect([Wizard::class, Step::class])
    ->toBeAbstract()
    ->and(Step::class)->toImplement(StepContract::class);

arch('events are immutable')
    ->expect('Invelity\WizardPackage\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('exceptions extend the SPL exceptions')
    ->expect('Invelity\WizardPackage\Exceptions')
    ->toExtend(Exception::class);

arch('stores implement the store contract')
    ->expect('Invelity\WizardPackage\Stores')
    ->toImplement(Store::class);

arch('the package does not reach for facades')
    ->expect('Invelity\WizardPackage')
    ->not->toUse('Illuminate\Support\Facades')
    ->ignoring('Invelity\WizardPackage\Facades');

arch('the package does not reach for global helpers')
    ->expect(['app', 'auth', 'config', 'event', 'now', 'request', 'response', 'route', 'session', 'trans', '__', 'url', 'view'])
    ->not->toBeUsedIn('Invelity\WizardPackage');
