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

    expect($report)->toHaveKey('Summary');
});

it('can get the forecast for a specific day', function () {
    $forecast = $this->buienradar->forecast()->forDay(0);

    expect($forecast)->toBeInstanceOf(Forecast::class)
        ->and($forecast->toArray())->toHaveKey('day');
});

it('can get the five day forecast', function () {
    $forecasts = $this->buienradar->forecast()->forFiveDays();

    expect($forecasts)->toBeArray()
        ->and($forecasts[0])->toBeInstanceOf(Forecast::class);
});
