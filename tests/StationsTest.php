<?php

use Baspa\Buienradar\ActualForecast;
use Baspa\Buienradar\Buienradar;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function fakeStationsBuienradar(string $body): Buienradar
{
    $mock = new MockHandler(array_map(fn () => new Response(200, [], $body), range(1, 4)));
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    return new Buienradar($client);
}

function fixtureStationsBuienradar(): Buienradar
{
    return fakeStationsBuienradar((string) file_get_contents(__DIR__.'/Fixtures/buienradar.json'));
}

/** @param array<string, mixed> $actual */
function sunAndStationsFeed(array $actual): string
{
    return (string) json_encode(['actual' => $actual]);
}

it('returns sunrise and sunset in Europe/Amsterdam', function () {
    $buienradar = fakeStationsBuienradar(sunAndStationsFeed([
        'sunrise' => '2026-11-13T07:55:00',
        'sunset' => '2026-11-13T16:47:00',
    ]));

    $sunrise = $buienradar->sunrise();
    $sunset = $buienradar->sunset();

    expect($sunrise)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($sunrise->format(DATE_ATOM))->toBe('2026-11-13T07:55:00+01:00')
        ->and($sunrise->getTimezone()->getName())->toBe('Europe/Amsterdam')
        ->and($sunset->format(DATE_ATOM))->toBe('2026-11-13T16:47:00+01:00');
});

it('applies summer time to sunrise', function () {
    $sunrise = fakeStationsBuienradar(sunAndStationsFeed(['sunrise' => '2026-06-21T05:18:00']))->sunrise();

    expect($sunrise->format(DATE_ATOM))->toBe('2026-06-21T05:18:00+02:00');
});

it('returns null for sunrise and sunset when the feed has none', function () {
    $buienradar = fixtureStationsBuienradar();

    expect($buienradar->sunrise())->toBeNull()
        ->and($buienradar->sunset())->toBeNull();
});

it('returns null for a sunrise that is not a full local date and time', function (mixed $value) {
    expect(fakeStationsBuienradar(sunAndStationsFeed(['sunrise' => $value]))->sunrise())->toBeNull();
})->with([
    'date only' => '2026-11-13',
    'without seconds' => '2026-11-13T07:55',
    'with offset' => '2026-11-13T07:55:00+01:00',
    'impossible date' => '2026-02-30T07:55:00',
    'not a string' => 1763016900,
    'empty' => '',
]);

it('returns every station measurement as a list', function () {
    $stations = fixtureStationsBuienradar()->stations();

    expect($stations)->toHaveCount(3)
        ->and(array_keys($stations))->toBe([0, 1, 2])
        ->and($stations)->each->toBeInstanceOf(ActualForecast::class)
        ->and(array_map(fn (ActualForecast $station) => $station->stationname, $stations))
        ->toBe(['Meetstation Arnhem', 'Meetstation De Bilt', 'Meetstation Volkel']);
});

it('finds the station nearest to a coordinate', function () {
    $buienradar = fixtureStationsBuienradar();

    expect($buienradar->nearestStation(52.1, 5.18)?->stationname)->toBe('Meetstation De Bilt')
        ->and($buienradar->nearestStation(51.6, 5.8)?->stationname)->toBe('Meetstation Volkel');
});

it('skips stations without coordinates when finding the nearest one', function () {
    $buienradar = fakeStationsBuienradar(sunAndStationsFeed(['stationmeasurements' => [
        ['stationname' => 'Meetstation Zonder Coördinaten'],
        ['stationname' => 'Meetstation Arnhem', 'lat' => 51.98, 'lon' => 5.88],
    ]]));

    expect($buienradar->nearestStation(0.0, 0.0)?->stationname)->toBe('Meetstation Arnhem');
});

it('returns null when no station has coordinates', function () {
    $buienradar = fakeStationsBuienradar(sunAndStationsFeed(['stationmeasurements' => [
        ['stationname' => 'Meetstation Zonder Coördinaten'],
        ['stationname' => 'Meetstation Zonder Lengtegraad', 'lat' => 52.1],
        ['stationname' => 'Meetstation Zonder Breedtegraad', 'lon' => 5.18, 'lat' => null],
    ]]));

    expect($buienradar->nearestStation(52.1, 5.18))->toBeNull();
});

it('returns no stations, no nearest station and no sun times for an empty feed', function () {
    $buienradar = fakeStationsBuienradar('{}');

    expect($buienradar->stations())->toBe([])
        ->and($buienradar->nearestStation(52.1, 5.18))->toBeNull()
        ->and($buienradar->sunrise())->toBeNull()
        ->and($buienradar->sunset())->toBeNull();
});
