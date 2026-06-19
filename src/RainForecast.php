<?php

namespace Baspa\Buienradar;

class RainForecast
{
    public function __construct(
        public string $time,
        public int $value,
        public float $mm,
    ) {}

    public static function fromLine(string $line): self
    {
        [$rawValue, $time] = array_pad(explode('|', trim($line), 2), 2, '');

        $value = (int) trim($rawValue);

        return new self(
            trim($time),
            $value,
            $value === 0 ? 0.0 : round(10 ** (($value - 109) / 32), 2),
        );
    }

    public function isDry(): bool
    {
        return $this->value === 0;
    }

    /** @return array{time: string, value: int, mm: float} */
    public function toArray(): array
    {
        return [
            'time' => $this->time,
            'value' => $this->value,
            'mm' => $this->mm,
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
