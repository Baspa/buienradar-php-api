<?php

namespace Baspa\Buienradar;

class Forecast
{
    public function __construct(
        public string $id,
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
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['$id'],
            $data['day'],
            $data['mintemperature'],
            $data['maxtemperature'],
            $data['mintemperatureMax'],
            $data['mintemperatureMin'],
            $data['maxtemperatureMax'],
            $data['maxtemperatureMin'],
            $data['rainChance'],
            $data['sunChance'],
            $data['windDirection'],
            $data['wind'],
            $data['mmRainMin'],
            $data['mmRainMax'],
            $data['weatherdescription'],
            $data['iconurl'],
            $data['fullIconUrl']
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
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
        return json_encode($this->toArray());
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
