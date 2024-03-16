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

    /** @var array<string, mixed> */
    private array $forecast;

    public function __construct()
    {
        $this->client = new Client();
    }

    /** @return array<string, mixed> */
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

    /** @return array<string, mixed> */
    public function get(): array
    {
        return $this->forecast;
    }

    public function actualForecastForStation(MeasuringStation $measuringStation): ?ActualForecast
    {
        $data = $this->fetchData();
        $measurements = $data['actual']['stationmeasurements'] ?? [];

        foreach ($measurements as $measurement) {
            if ($measurement['stationname'] === $measuringStation->value) {
                return ActualForecast::fromArray($measurement);
                return $measurement;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function report(): array
    {
        return $this->forecast['weatherreport'] ?? [];
    }

    /** @return array<string, mixed> */
    public function shortTerm(): array
    {
        return $this->forecast['shortterm'] ?? [];
    }

    /** @return array<string, mixed> */
    public function longTerm(): array
    {
        return $this->forecast['longterm'] ?? [];
    }

    /** @return array<string, mixed> */
    public function forFiveDays(): array
    {
        return array_map(fn ($forecast) => Forecast::fromArray($forecast), $this->forecast['fivedayforecast'] ?? []);
    }

    public function forDay(int $day): Forecast
    {
        return Forecast::fromArray($this->forecast['fivedayforecast'][$day] ?? []);
    }
}
