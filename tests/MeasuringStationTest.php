<?php

use Baspa\Buienradar\ActualForecast;
use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

it('aliases the misspelled cases with correctly spelled constants', function () {
    expect(MeasuringStation::GILZE_RIJEN)->toBe(MeasuringStation::GILZE_RIJEEN)
        ->and(MeasuringStation::HOUTRIBDIJK)->toBe(MeasuringStation::HOULTRIBDIJK);
});

it('has a case for Meetstation Horst', function () {
    expect(MeasuringStation::HORST->value)->toBe('Meetstation Horst')
        ->and(MeasuringStation::from('Meetstation Horst'))->toBe(MeasuringStation::HORST);
});

it('can get the actual forecast for Meetstation Horst', function () {
    $body = (string) json_encode([
        'actual' => [
            'stationmeasurements' => [
                ['stationid' => 6375, 'stationname' => 'Meetstation Volkel', 'regio' => 'Uden'],
                ['stationid' => 6392, 'stationname' => 'Meetstation Horst', 'regio' => 'Venlo', 'temperature' => 9.4],
            ],
        ],
    ]);

    $buienradar = new Buienradar(new Client([
        'handler' => HandlerStack::create(new MockHandler([new Response(200, [], $body)])),
    ]));

    $forecast = $buienradar->actualForecastForStation(MeasuringStation::HORST);

    expect($forecast)->toBeInstanceOf(ActualForecast::class)
        ->and($forecast->stationid)->toBe(6392)
        ->and($forecast->stationname)->toBe('Meetstation Horst')
        ->and($forecast->regio)->toBe('Venlo')
        ->and($forecast->temperature)->toBe(9.4);
});
