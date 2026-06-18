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
            (int) ($data['stationid'] ?? 0),
            (string) ($data['stationname'] ?? ''),
            (float) ($data['lat'] ?? 0),
            (float) ($data['lon'] ?? 0),
            isset($data['regio']) ? (string) $data['regio'] : null,
            (string) ($data['timestamp'] ?? ''),
            (string) ($data['weatherdescription'] ?? ''),
            (string) ($data['iconurl'] ?? ''),
            (string) ($data['fullIconUrl'] ?? ''),
            (string) ($data['graphUrl'] ?? ''),
            isset($data['winddirection']) ? (string) $data['winddirection'] : null,
            $float('airpressure'),
            $float('temperature'),
            $float('groundtemperature'),
            $float('feeltemperature'),
            $float('visibility'),
            $float('windgusts'),
            $float('windspeed'),
            isset($data['windspeedBft']) ? (int) $data['windspeedBft'] : null,
            $float('humidity'),
            $float('precipitation'),
            $float('sunpower'),
            $float('rainFallLast24Hour'),
            $float('rainFallLastHour'),
            $float('winddirectiondegrees')
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
