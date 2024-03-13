<?php

use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\Enum\MeasuringStation;

it('can get the forecast for a specific measurement station', function () {
    $forecast = Buienradar::actualForecastForStation(MeasuringStation::VOLKEL);

    expect($forecast)->toHaveKey('stationname');
});

it('can get the short term forecast', function () {
    $forecast = Buienradar::forecast()->shortTerm();

    expect($forecast)->toHaveKey('forecast');
});

it('can get the long term forecast', function () {
    $forecast = Buienradar::forecast()->longTerm();

    expect($forecast)->toHaveKey('forecast');
});

it('can get the weather report', function () {
    $forecast = Buienradar::forecast()->report();

    expect($forecast)->toHaveKey('summary');
});
