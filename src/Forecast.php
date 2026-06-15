<?php

namespace Baspa\Buienradar;

class Forecast
{
    public function __construct(
        public string $day,
        public string $mintemperature,
        public string $maxtemperature,
        public int $mintemperatureMax,
        public int $mintemperatureMin,
        public int $maxtemperatureMax,
        public int $maxtemperatureMin,
        public int $rainChance,
        public int $sunChance,
        public string $windDirection,
        public int $wind,
        public float $mmRainMin,
        public float $mmRainMax,
        public string $weatherdescription,
        public string $iconurl,
        public string $fullIconUrl
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['Day'] ?? ''),
            (string) ($data['MinTemperature'] ?? ''),
            (string) ($data['MaxTemperature'] ?? ''),
            (int) ($data['MinTemperatureMax'] ?? 0),
            (int) ($data['MinTemperatureMin'] ?? 0),
            (int) ($data['MaxTemperatureMax'] ?? 0),
            (int) ($data['MaxTemperatureMin'] ?? 0),
            (int) ($data['RainChance'] ?? 0),
            (int) ($data['SunChance'] ?? 0),
            (string) ($data['WindDirection'] ?? ''),
            (int) ($data['WindBeaufort'] ?? 0),
            (float) ($data['RainMinMm'] ?? 0),
            (float) ($data['RainMaxMm'] ?? 0),
            (string) ($data['WeatherDescription'] ?? ''),
            (string) ($data['IconUrl'] ?? ''),
            (string) ($data['FullIconUrl'] ?? '')
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'day' => $this->day,
            'mintemperature' => $this->mintemperature,
            'maxtemperature' => $this->maxtemperature,
            'mintemperatureMax' => $this->mintemperatureMax,
            'mintemperatureMin' => $this->mintemperatureMin,
            'maxtemperatureMax' => $this->maxtemperatureMax,
            'maxtemperatureMin' => $this->maxtemperatureMin,
            'rainChance' => $this->rainChance,
            'sunChance' => $this->sunChance,
            'windDirection' => $this->windDirection,
            'wind' => $this->wind,
            'mmRainMin' => $this->mmRainMin,
            'mmRainMax' => $this->mmRainMax,
            'weatherdescription' => $this->weatherdescription,
            'iconurl' => $this->iconurl,
            'fullIconUrl' => $this->fullIconUrl,
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
