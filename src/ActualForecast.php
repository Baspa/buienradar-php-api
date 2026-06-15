<?php

namespace Baspa\Buienradar;

class ActualForecast
{
    public function __construct(
        public int $stationid,
        public string $stationname,
        public float $lat,
        public float $lon,
        public ?string $regio,
        public string $timestamp,
        public string $weatherdescription,
        public string $iconurl,
        public string $fullIconUrl,
        public string $graphUrl,
        public ?string $winddirection,
        public ?float $airpressure,
        public ?float $temperature,
        public ?float $groundtemperature,
        public ?float $feeltemperature,
        public ?float $visibility,
        public ?float $windgusts,
        public ?float $windspeed,
        public ?int $windspeedBft,
        public ?float $humidity,
        public ?float $precipitation,
        public ?float $sunpower,
        public ?float $rainFallLast24Hour,
        public ?float $rainFallLastHour,
        public ?float $winddirectiondegrees
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $float = fn (string $key): ?float => isset($data[$key]) ? (float) $data[$key] : null;

        return new self(
            (int) ($data['StationId'] ?? 0),
            (string) ($data['StationName'] ?? ''),
            (float) ($data['Latitude'] ?? 0),
            (float) ($data['Longitude'] ?? 0),
            isset($data['Region']) ? (string) $data['Region'] : null,
            (string) ($data['Timestamp'] ?? ''),
            (string) ($data['WeatherDescription'] ?? ''),
            (string) ($data['IconUrl'] ?? ''),
            (string) ($data['FullIconUrl'] ?? ''),
            (string) ($data['GraphUrl'] ?? ''),
            isset($data['WindDirection']) ? (string) $data['WindDirection'] : null,
            $float('AirPressure'),
            $float('Temperature'),
            $float('GroundTemperature'),
            $float('FeelTemperature'),
            $float('Visibility'),
            $float('WindGusts'),
            $float('Windspeed'),
            isset($data['WindspeedBeaufort']) ? (int) $data['WindspeedBeaufort'] : null,
            $float('Humidity'),
            $float('Precipitation'),
            $float('Sunpower'),
            $float('RainfallLast24Hour'),
            $float('RainfallLastHour'),
            $float('WindDirectionDegrees')
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'stationid' => $this->stationid,
            'stationname' => $this->stationname,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'regio' => $this->regio,
            'timestamp' => $this->timestamp,
            'weatherdescription' => $this->weatherdescription,
            'iconurl' => $this->iconurl,
            'fullIconUrl' => $this->fullIconUrl,
            'graphUrl' => $this->graphUrl,
            'winddirection' => $this->winddirection,
            'airpressure' => $this->airpressure,
            'temperature' => $this->temperature,
            'groundtemperature' => $this->groundtemperature,
            'feeltemperature' => $this->feeltemperature,
            'visibility' => $this->visibility,
            'windgusts' => $this->windgusts,
            'windspeed' => $this->windspeed,
            'windspeedBft' => $this->windspeedBft,
            'humidity' => $this->humidity,
            'precipitation' => $this->precipitation,
            'sunpower' => $this->sunpower,
            'rainFallLast24Hour' => $this->rainFallLast24Hour,
            'rainFallLastHour' => $this->rainFallLastHour,
            'winddirectiondegrees' => $this->winddirectiondegrees,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray()) ?: '';
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
