<?php

use Baspa\Buienradar\ActualForecast;
use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\Enum\MeasuringStation;
use Baspa\Buienradar\Forecast;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function fakeBuienradar(): Buienradar
{
    $body = (string) file_get_contents(__DIR__.'/Fixtures/buienradar.json');

    // Queue enough identical responses for the multiple fetches a single test may trigger.
    $mock = new MockHandler(array_fill(0, 5, new Response(200, [], $body)));
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    return new Buienradar($client);
}

beforeEach(function () {
    $this->buienradar = fakeBuienradar();
});

it('can get the forecast for a specific measurement station', function () {
    $forecast = $this->buienradar->actualForecastForStation(MeasuringStation::VOLKEL);

    expect($forecast)->toBeInstanceOf(ActualForecast::class)
        ->and($forecast->stationname)->toBe('Meetstation Volkel')
        ->and($forecast->toArray())->toHaveKey('stationname');
});

it('returns null for an unknown measurement station', function () {
    $forecast = (new Buienradar(new Client([
        'handler' => HandlerStack::create(new MockHandler([new Response(200, [], '{}')])),
    ])))->actualForecastForStation(MeasuringStation::VOLKEL);

    expect($forecast)->toBeNull();
});

it('can get the short term forecast', function () {
    $forecast = $this->buienradar->forecast()->shortTerm();

    expect($forecast)->toHaveKey('Forecast');
});

it('can get the long term forecast', function () {
    $forecast = $this->buienradar->forecast()->longTerm();

    expect($forecast)->toHaveKey('Forecast');
});

it('can get the weather report', function () {
    $report = $this->buienradar->forecast()->report();

    expect($report)->toHaveKey('summary');
});

it('can get the forecast for a specific day', function (int $day, string $date) {
    $forecast = $this->buienradar->forecast()->forDay($day);

    expect($forecast)->toBeInstanceOf(Forecast::class)
        ->and($forecast->day)->toBe($date);
})->with([
    'today' => [0, '2026-06-15T00:00:00'],
    'last day' => [4, '2026-06-19T00:00:00'],
]);

it('rejects a day outside 0 to 4', function (int $day) {
    $this->buienradar->forecast()->forDay($day);
})->with([-1, 5])->throws(InvalidArgumentException::class);

it('can get the five day forecast', function () {
    $forecasts = $this->buienradar->forecast()->forFiveDays();

    expect($forecasts)->toBeArray()
        ->and($forecasts[0])->toBeInstanceOf(Forecast::class);
});
