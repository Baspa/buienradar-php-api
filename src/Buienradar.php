<?php

namespace Baspa\Buienradar;

use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class Buienradar
{
    private const URL = 'https://data.buienradar.nl/2.0/feed/json';

    private ClientInterface $client;

    /** @var array<string, mixed> */
    private array $forecast = [];

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client;
    }

    /** @return array<string, mixed> */
    private function fetchData(): array
    {
        try {
            $response = $this->client->request('GET', self::URL);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            // Handle the exception (log, throw a custom exception, etc.)
            return [];
        }
    }

    public function forecast(): self
    {
        $data = $this->fetchData();
        $this->forecast = $data['Forecast'] ?? [];

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
        $measurements = $data['Actual']['WeatherStationMeasurements'] ?? [];

        foreach ($measurements as $measurement) {
            if (($measurement['StationName'] ?? null) === $measuringStation->value) {
                return ActualForecast::fromArray($measurement);
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function report(): array
    {
        return $this->forecast['WeatherReport'] ?? [];
    }

    /** @return array<string, mixed> */
    public function shortTerm(): array
    {
        return $this->forecast['ShortTermForecast'] ?? [];
    }

    /** @return array<string, mixed> */
    public function longTerm(): array
    {
        return $this->forecast['LongTerm'] ?? [];
    }

    /** @return array<int, Forecast> */
    public function forFiveDays(): array
    {
        return array_map(fn ($forecast) => Forecast::fromArray($forecast), $this->forecast['FiveDayForecast'] ?? []);
    }

    public function forDay(int $day): Forecast
    {
        return Forecast::fromArray($this->forecast['FiveDayForecast'][$day] ?? []);
    }
}
