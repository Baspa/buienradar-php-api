<?php

namespace Baspa\Buienradar;

use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class Buienradar
{
    private const URL = 'https://data.buienradar.nl/2.0/feed/json';

    private const RAINTEXT_URL = 'https://gpsgadget.buienradar.nl/data/raintext';

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
            if (($measurement['stationname'] ?? null) === $measuringStation->value) {
                return ActualForecast::fromArray($measurement);
            }
        }

        return null;
    }

    /** @return array<int, RainForecast> */
    public function rainForecast(float $lat, float $lon): array
    {
        try {
            $response = $this->client->request('GET', self::RAINTEXT_URL, [
                'query' => ['lat' => $lat, 'lon' => $lon],
            ]);

            $body = $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($body)) ?: [];

        return array_values(array_map(
            fn (string $line) => RainForecast::fromLine($line),
            array_filter($lines, fn (string $line) => trim($line) !== ''),
        ));
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

    /** @return array<int, Forecast> */
    public function forFiveDays(): array
    {
        return array_map(fn ($forecast) => Forecast::fromArray($forecast), $this->forecast['fivedayforecast'] ?? []);
    }

    public function forDay(int $day): Forecast
    {
        return Forecast::fromArray($this->forecast['fivedayforecast'][$day] ?? []);
    }
}
