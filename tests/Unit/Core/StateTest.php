<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Invelity\WizardPackage\State;

it('reads the layout written by 1.x and keeps keys it does not own', function () {
    $state = State::fromArray([
        'wizard_id' => 'order-wizard',
        'current_step' => 'summary',
        'completed_steps' => ['calculator', 'newsletter', 'personal-data'],
        'steps' => [
            'calculator' => ['weight' => 2],
            'personal-data' => ['name' => 'Jane'],
            'summary' => null,
        ],
        'metadata' => ['carrier' => 'gls'],
        'started_at' => '2026-01-02T10:00:00+00:00',
        'packages' => [['weight' => 2]],
        'destination_country_code' => 'SK',
    ]);

    expect($state->current)->toBe('summary')
        ->and($state->completed)->toBe(['calculator', 'newsletter', 'personal-data'])
        ->and($state->skipped)->toBe([])
        ->and($state->data)->toBe(['calculator' => ['weight' => 2], 'personal-data' => ['name' => 'Jane']])
        ->and($state->metadata)->toBe(['carrier' => 'gls'])
        ->and($state->startedAt?->toIso8601String())->toBe('2026-01-02T10:00:00+00:00')
        ->and($state->completedAt)->toBeNull()
        ->and($state->attributes)->toBe([
            'wizard_id' => 'order-wizard',
            'packages' => [['weight' => 2]],
            'destination_country_code' => 'SK',
        ]);
});

it('writes a layout that 1.x code can still read', function () {
    $state = (new State)
        ->withCompleted('calculator', ['weight' => 2])
        ->withSkipped('newsletter')
        ->withCurrent('personal-data')
        ->withMetadata(['carrier' => 'gls'])
        ->withStartedAt(CarbonImmutable::parse('2026-01-02T10:00:00+00:00'));

    expect($state->toArray())->toBe([
        'version' => 2,
        'current_step' => 'personal-data',
        'completed_steps' => ['calculator', 'newsletter'],
        'skipped_steps' => ['newsletter'],
        'steps' => ['calculator' => ['weight' => 2]],
        'metadata' => ['carrier' => 'gls'],
        'started_at' => '2026-01-02T10:00:00+00:00',
        'completed_at' => null,
        'status' => 'in_progress',
    ]);
});

it('survives a round trip without losing anything', function () {
    $raw = [
        'wizard_id' => 'order-wizard',
        'current_step' => 'summary',
        'completed_steps' => ['calculator', 'newsletter'],
        'skipped_steps' => ['newsletter'],
        'steps' => ['calculator' => ['weight' => 2]],
        'metadata' => ['carrier' => 'gls'],
        'started_at' => '2026-01-02T10:00:00+00:00',
        'completed_at' => '2026-01-02T11:00:00+00:00',
        'cash_on_delivery' => true,
    ];

    $state = State::fromArray(State::fromArray($raw)->toArray());

    expect($state->toArray())->toBe([
        'wizard_id' => 'order-wizard',
        'cash_on_delivery' => true,
        'version' => 2,
        'current_step' => 'summary',
        'completed_steps' => ['calculator', 'newsletter'],
        'skipped_steps' => ['newsletter'],
        'steps' => ['calculator' => ['weight' => 2]],
        'metadata' => ['carrier' => 'gls'],
        'started_at' => '2026-01-02T10:00:00+00:00',
        'completed_at' => '2026-01-02T11:00:00+00:00',
        'status' => 'completed',
    ]);
});

it('separates skipped steps from processed ones', function () {
    $state = State::fromArray([
        'completed_steps' => ['calculator', 'newsletter'],
        'skipped_steps' => ['newsletter'],
    ]);

    expect($state->completed)->toBe(['calculator'])
        ->and($state->skipped)->toBe(['newsletter'])
        ->and($state->hasCompleted('calculator'))->toBeTrue()
        ->and($state->hasCompleted('newsletter'))->toBeFalse()
        ->and($state->hasSkipped('newsletter'))->toBeTrue()
        ->and($state->hasFinished('newsletter'))->toBeTrue()
        ->and($state->hasFinished('summary'))->toBeFalse();
});

it('tolerates malformed stored values', function () {
    $state = State::fromArray([
        'current_step' => 42,
        'completed_steps' => 'calculator',
        'skipped_steps' => [1, 'newsletter', 'newsletter'],
        'steps' => 'nope',
        'metadata' => null,
        'started_at' => 'not a date',
        'completed_at' => '',
    ]);

    expect($state->current)->toBeNull()
        ->and($state->completed)->toBe([])
        ->and($state->skipped)->toBe(['newsletter'])
        ->and($state->data)->toBe([])
        ->and($state->metadata)->toBe([])
        ->and($state->startedAt)->toBeNull()
        ->and($state->completedAt)->toBeNull();
});

it('is immutable', function () {
    $original = new State;

    $changed = $original->withCompleted('calculator', ['weight' => 2]);

    expect($original->completed)->toBe([])
        ->and($changed)->not->toBe($original)
        ->and($changed->completed)->toBe(['calculator']);
});

it('moves a step between processed and skipped', function () {
    $state = (new State)->withSkipped('newsletter')->withCompleted('newsletter', ['email' => 'a@b.c']);

    expect($state->completed)->toBe(['newsletter'])
        ->and($state->skipped)->toBe([]);

    $state = $state->withSkipped('newsletter');

    expect($state->completed)->toBe([])
        ->and($state->skipped)->toBe(['newsletter'])
        ->and($state->data('newsletter'))->toBe(['email' => 'a@b.c']);
});

it('does not list a step twice', function () {
    $state = (new State)
        ->withCompleted('calculator', ['weight' => 1])
        ->withCompleted('calculator', ['weight' => 2]);

    expect($state->completed)->toBe(['calculator'])
        ->and($state->data('calculator'))->toBe(['weight' => 2]);
});

it('reopens steps but keeps their data for prefilling', function () {
    $state = (new State)
        ->withCompleted('calculator', ['weight' => 2])
        ->withSkipped('newsletter')
        ->withCompleted('summary', ['accepted' => true])
        ->withCompletedAt(CarbonImmutable::now())
        ->withReopened('calculator', 'newsletter');

    expect($state->completed)->toBe(['summary'])
        ->and($state->skipped)->toBe([])
        ->and($state->data('calculator'))->toBe(['weight' => 2])
        ->and($state->isCompleted())->toBeFalse();
});

it('knows whether the wizard was started and completed', function () {
    $state = new State;

    expect($state->isStarted())->toBeFalse()
        ->and($state->isCompleted())->toBeFalse();

    $state = $state->withStartedAt(CarbonImmutable::now())->withCompletedAt(CarbonImmutable::now());

    expect($state->isStarted())->toBeTrue()
        ->and($state->isCompleted())->toBeTrue()
        ->and($state->withCompletedAt(null)->isCompleted())->toBeFalse();
});

it('returns an empty array for steps without data', function () {
    expect((new State)->data('calculator'))->toBe([]);
});
