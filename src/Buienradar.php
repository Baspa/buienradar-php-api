<?php

namespace Baspa\Buienradar;

use Baspa\Buienradar\Enum\MeasuringStation;
use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class Buienradar
{
    private const URL = 'https://data.buienradar.nl/2.0/feed/json';

    private const RAINTEXT_URL = 'https://gpsgadget.buienradar.nl/data/raintext';

    private const SUN_TIME_FORMAT = 'Y-m-d\TH:i:s';

    private const EARTH_RADIUS_KM = 6371;

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

    public function sunrise(): ?DateTimeImmutable
    {
        return $this->sunTime('sunrise');
    }

    public function sunset(): ?DateTimeImmutable
    {
        return $this->sunTime('sunset');
    }

    /** @return array<int, ActualForecast> */
    public function stations(): array
    {
        return array_map(
            fn (array $measurement) => ActualForecast::fromArray($measurement),
            $this->stationMeasurements(),
        );
    }

    public function nearestStation(float $lat, float $lon): ?ActualForecast
    {
        $nearest = null;
        $nearestDistance = INF;

        foreach ($this->stationMeasurements() as $measurement) {
            // ActualForecast turns missing coordinates into 0.0, so check the raw values.
            if (! is_numeric($measurement['lat'] ?? null) || ! is_numeric($measurement['lon'] ?? null)) {
                continue;
            }

            $distance = $this->distanceInKm($lat, $lon, (float) $measurement['lat'], (float) $measurement['lon']);

            if ($distance < $nearestDistance) {
                $nearest = $measurement;
                $nearestDistance = $distance;
            }
        }

        return $nearest === null ? null : ActualForecast::fromArray($nearest);
    }

    private function sunTime(string $key): ?DateTimeImmutable
    {
        $value = $this->fetchData()['actual'][$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $time = DateTimeImmutable::createFromFormat('!'.self::SUN_TIME_FORMAT, $value, new DateTimeZone('Europe/Amsterdam'));

        // createFromFormat rolls impossible dates over (02-30 becomes 03-02) instead of failing.
        if ($time === false || $time->format(self::SUN_TIME_FORMAT) !== $value) {
            return null;
        }

        return $time;
    }

    /** @return array<int, array<string, mixed>> */
    private function stationMeasurements(): array
    {
        $measurements = $this->fetchData()['actual']['stationmeasurements'] ?? [];

        return is_array($measurements) ? array_values(array_filter($measurements, 'is_array')) : [];
    }

    private function distanceInKm(float $fromLat, float $fromLon, float $toLat, float $toLon): float
    {
        $latDelta = deg2rad($toLat - $fromLat);
        $lonDelta = deg2rad($toLon - $fromLon);

        $haversine = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lonDelta / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($haversine)));
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
