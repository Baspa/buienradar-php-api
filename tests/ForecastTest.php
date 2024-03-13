<?php

use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\Enum\MeasuringStation;

beforeEach(function () {
    $this->buienradar = new Buienradar();
});

it('can get the forecast for a specific measurement station', function () {
    $forecast = $this->buienradar->actualForecastForStation(MeasuringStation::VOLKEL);

    expect($forecast)->toHaveKey('stationname');
});

it('can get the short term forecast', function () {
    $forecast = $this->buienradar->forecast()->shortTerm();

    expect($forecast)->toHaveKey('forecast');
});

it('can get the long term forecast', function () {
    $forecast = $this->buienradar->forecast()->longTerm();

    expect($forecast)->toHaveKey('forecast');
});

it('can get the weather report', function () {
    $forecast = $this->buienradar->forecast()->report();

    expect($forecast)->toHaveKey('summary');
});
