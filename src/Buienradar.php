<?php

namespace Baspa\Buienradar;

namespace Baspa\Buienradar;

use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Buienradar
{
    private const URL = 'https://data.buienradar.nl/2.0/feed/json';

    private Client $client;

    private array $forecast;

    public function __construct()
    {
        $this->client = new Client();
    }

    private function fetchData(): array
    {
        try {
            $response = $this->client->get(self::URL);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // Handle the exception (log, throw a custom exception, etc.)
            return [];
        }
    }

    public function forecast(): self
    {
        $data = $this->fetchData();
        $this->forecast = $data['forecast'] ?? [];

        return $this;
    }

    public function get(): array
    {
        return $this->forecast;
    }

    public function actualForecastForStation(MeasuringStation $measuringStation): array
    {
        $data = $this->fetchData();
        $measurements = $data['actual']['stationmeasurements'] ?? [];

        foreach ($measurements as $measurement) {
            if ($measurement['stationname'] === $measuringStation->value) {
                return $measurement;
            }
        }

        return [];
    }

    public function report(): array
    {
        return $this->forecast['weatherreport'] ?? [];
    }

    public function shortTerm(): array
    {
        return $this->forecast['shortterm'] ?? [];
    }

    public function longTerm(): array
    {
        return $this->forecast['longterm'] ?? [];
    }

    public function forFiveDays(): array
    {
        return array_map(fn ($forecast) => Forecast::fromArray($forecast), $this->forecast['fivedayforecast'] ?? []);
    }

    public function forDay(int $day): Forecast
    {
        return Forecast::fromArray($this->forecast['fivedayforecast'][$day] ?? []);
    }
}
