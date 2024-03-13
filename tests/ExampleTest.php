<?php

use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\Enum\MeasuringStation;

it('can get the forecast for a specific measurement station', function () {
    $forecast = Buienradar::actualForecastForStation(MeasuringStation::VOLKEL);

    expect($forecast)->toHaveKey('stationname');
});
