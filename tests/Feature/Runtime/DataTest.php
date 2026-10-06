<?php

declare(strict_types=1);

use Invelity\WizardPackage\Exceptions\StepNotFoundException;
use Invelity\WizardPackage\Facades\Wizard;
use Invelity\WizardPackage\Tests\Fixtures\Wizards\OrderWizard;

beforeEach(function () {
    $this->wizard = Wizard::for(OrderWizard::class);
});

it('reads and writes metadata with dot notation', function () {
    $this->wizard->putMetadata('carrier', 'gls');
    $this->wizard->putMetadata(['parcels.0.weight' => 2, 'cash_on_delivery' => true]);

    expect($this->wizard->metadata())->toBe([
        'carrier' => 'gls',
        'parcels' => [['weight' => 2]],
        'cash_on_delivery' => true,
    ])
        ->and($this->wizard->metadata('parcels.0.weight'))->toBe(2)
        ->and($this->wizard->metadata('missing', 'default'))->toBe('default')
        ->and(Wizard::for(OrderWizard::class)->metadata('carrier'))->toBe('gls');
});

it('forgets metadata', function () {
    $this->wizard->putMetadata(['carrier' => 'gls', 'parcels' => [['weight' => 2]], 'note' => 'x']);

    $this->wizard->forgetMetadata('parcels.0');
    $this->wizard->forgetMetadata(['note']);

    expect($this->wizard->metadata())->toBe(['carrier' => 'gls', 'parcels' => []]);
});

it('starts the wizard when metadata is stored first', function () {
    $this->wizard->putMetadata('carrier', 'gls');

    expect($this->wizard->exists())->toBeTrue()
        ->and($this->wizard->state()->isStarted())->toBeTrue();
});

it('lets conditional steps react to metadata', function () {
    expect($this->wizard->navigation()->item('billing'))->not->toBeNull();

    $this->wizard->putMetadata('plan', 'free');

    expect($this->wizard->navigation()->item('billing'))->toBeNull()
        ->and($this->wizard->progress()->total)->toBe(4);
});

it('returns the data of every step', function () {
    $this->wizard->process('calculator', ['weight' => '2']);

    expect($this->wizard->data())->toBe(['calculator' => ['weight' => '2', 'price' => 9.0]])
        ->and($this->wizard->data('personal-data'))->toBe([]);
});

it('refuses the data of an unknown step', function () {
    $this->wizard->data('payment');
})->throws(StepNotFoundException::class);
