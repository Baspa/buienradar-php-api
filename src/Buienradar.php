<?php

namespace Baspa\Buienradar;

use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;

class Buienradar
{
    private static string $url = 'https://data.buienradar.nl/2.0/feed/json';

    public static ?Client $client = null;

    public static ?array $forecast = null;

    private static function init()
    {
        if (self::$client !== null) {
            return;
        }
        self::$client = new Client();
    }

    public static function forecast(): self
    {
        self::init();

        $response = self::$client->get(self::$url);

        self::$forecast = json_decode($response->getBody()->getContents(), true)['forecast'];

        return new self;
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

    public static function report(): array
    {
        self::init();

        return self::$forecast['weatherreport'];
    }

    public static function shortTerm(): array
    {
        self::init();

        return self::$forecast['shortterm'];
    }

    public static function longTerm(): array
    {
        self::init();

        return self::$forecast['longterm'];
    }
}
