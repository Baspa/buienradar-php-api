<?php

namespace Baspa\Buienradar;

use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class Buienradar
{
    private const URL = 'https://data.buienradar.nl/2.0/feed/json';

    private const RAINTEXT_URL = 'https://gps.buienradar.nl/getrr.php';

    private const RAINTEXT_LINE = '/^(?:[01]\d{2}|2[0-4]\d|25[0-5])\|(?:[01]\d|2[0-3]):[0-5]\d$/';

    private ClientInterface $client;

    /** @var array<string, mixed> */
    private array $forecast = [];

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
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
        $data = json_decode($this->requestBody(self::URL) ?? '', true);

        return is_array($data) ? $data : [];
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
        $measurements = $this->fetchData()['actual']['stationmeasurements'] ?? [];

        if (! is_array($measurements)) {
            return null;
        }

        foreach ($measurements as $measurement) {
            if (is_array($measurement) && ($measurement['stationname'] ?? null) === $measuringStation->value) {
                return ActualForecast::fromArray($measurement);
            }
        }

        return null;
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

    /** @throws \InvalidArgumentException when $day is not between 0 and 4 */
    public function forDay(int $day): Forecast
    {
        if ($day < 0 || $day > 4) {
            throw new \InvalidArgumentException(sprintf('Day must be between 0 (today) and 4, got %d.', $day));
        }

        return Forecast::fromArray($this->forecast['fivedayforecast'][$day] ?? []);
    }
}
