<?php

use Baspa\Buienradar\RainForecast;

it('parses a raintext line into time, value and mm', function () {
    $rain = RainForecast::fromLine('077|14:25');

    expect($rain)->toBeInstanceOf(RainForecast::class)
        ->and($rain->time)->toBe('14:25')
        ->and($rain->value)->toBe(77)
        ->and($rain->mm)->toBe(round(10 ** ((77 - 109) / 32), 2));
});

it('reports mm as 0.0 and isDry true when value is 0', function () {
    $rain = RainForecast::fromLine('000|14:30');

    expect($rain->value)->toBe(0)
        ->and($rain->mm)->toBe(0.0)
        ->and($rain->isDry())->toBeTrue();
});

it('reports isDry false when it is raining', function () {
    expect(RainForecast::fromLine('077|14:25')->isDry())->toBeFalse();
});

it('serialises to array and json', function () {
    $rain = RainForecast::fromLine('077|14:25');

    expect($rain->toArray())->toBe(['time' => '14:25', 'value' => 77, 'mm' => $rain->mm])
        ->and($rain->toJson())->toBe(json_encode($rain->toArray()))
        ->and((string) $rain)->toBe($rain->toJson());
});
