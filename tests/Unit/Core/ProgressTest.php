<?php

declare(strict_types=1);

use Invelity\WizardPackage\Progress;

it('computes a rounded percentage', function (int $completed, int $total, int $percentage) {
    expect((new Progress($completed, $total))->percentage())->toBe($percentage);
})->with([
    'nothing done' => [0, 3, 0],
    'one of three' => [1, 3, 33],
    'two of three' => [2, 3, 67],
    'all done' => [3, 3, 100],
    'no steps' => [0, 0, 100],
    'more than total' => [4, 3, 100],
]);

it('counts the remaining steps', function () {
    expect((new Progress(1, 3))->remaining())->toBe(2)
        ->and((new Progress(5, 3))->remaining())->toBe(0);
});

it('serialises to an array and JSON', function () {
    $progress = new Progress(1, 4);

    expect($progress->toArray())->toBe(['completed_steps' => 1, 'total_steps' => 4, 'percentage' => 25])
        ->and(json_encode($progress))->toBe('{"completed_steps":1,"total_steps":4,"percentage":25}');
});
