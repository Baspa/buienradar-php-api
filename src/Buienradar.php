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

    private const RAINTEXT_URL = 'https://gps.buienradar.nl/getrr.php';

    private const RAINTEXT_LINE = '/^(?:[01]\d{2}|2[0-4]\d|25[0-5])\|(?:[01]\d|2[0-3]):[0-5]\d$/';

    private const SUN_TIME_FORMAT = 'Y-m-d\TH:i:s';

    private const EARTH_RADIUS_KM = 6371;

    private ClientInterface $client;

    /** @var array<string, mixed> */
    private array $forecast = [];

    /** @var array<string, mixed>|null */
    private ?array $feed = null;

    private int $feedFetchedAt = 0;

    public function __construct(?ClientInterface $client = null, private int $cacheSeconds = 60)
    {
        $this->client = $client ?? new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
    }

    public function refresh(): self
    {
        $this->feed = null;

        return $this;
    }

    /** @param array<string, mixed> $options */
    private function requestBody(string $url, array $options = []): ?string
    {
        try {
            $response = $this->client->request('GET', $url, $options);
        } catch (GuzzleException) {
            return null;
        }

        $status = $response->getStatusCode();

        return $status >= 200 && $status < 300 ? (string) $response->getBody() : null;
    }

    /** @return array<string, mixed> */
    private function fetchData(): array
    {
        if ($this->feed !== null && time() - $this->feedFetchedAt < $this->cacheSeconds) {
            return $this->feed;
        }

        $data = json_decode($this->requestBody(self::URL) ?? '', true);

        if (! is_array($data)) {
            return [];
        }

        $this->feedFetchedAt = time();

        return $this->feed = $data;
    }

    public function forecast(): self
    {
        $forecast = $this->fetchData()['forecast'] ?? [];
        $this->forecast = is_array($forecast) ? $forecast : [];

        return $this;
    }

    /** @return array<string, mixed> */
    public function get(): array
    {
        return $this->forecast;
    }

    public function actualForecastForStation(MeasuringStation $measuringStation): ?ActualForecast
    {
        foreach ($this->stationMeasurements() as $measurement) {
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
        $body = $this->requestBody(self::RAINTEXT_URL, [
            'query' => ['lat' => $lat, 'lon' => $lon],
        ]);

        if ($body === null) {
            return [];
        }

        $lines = array_map('trim', preg_split('/\r\n|\r|\n/', $body) ?: []);

        return array_values(array_map(
            fn (string $line) => RainForecast::fromLine($line),
            preg_grep(self::RAINTEXT_LINE, $lines) ?: [],
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
