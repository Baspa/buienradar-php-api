<?php

namespace Baspa\Buienradar;

use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;

class Buienradar
{
    private static string $url = 'https://data.buienradar.nl/2.0/feed/json';

    public static ?Client $client = null;

    private static function init()
    {
        if (self::$client !== null) {
            return;
        }
        self::$client = new Client();
    }

    public static function getWeatherForecast(): array
    {
        self::init();

        $response = self::$client->get(self::$url);

        return json_decode($response->getBody()->getContents(), true)['forecast'];
    }

    public static function actualForecastForStation(MeasuringStation $measuringStation): array
    {
        self::init();

        $response = self::$client->get(self::$url);
        $measurements = json_decode($response->getBody()->getContents(), true)['actual']['stationmeasurements'];

        foreach ($measurements as $measurement) {
            if ($measurement['stationname'] === $measuringStation->value) {
                return $measurement;
            }
        }

        return [];
    }
}