# Raintext / neerslagvoorspelling — ontwerp

**Datum:** 2026-06-19
**Package:** `baspa/buienradar-php-api`

## Doel

Een tweede Buienradar-feed toevoegen: de raintext-feed met de korte-termijn
neerslagvoorspelling ("binnen X minuten begint het te regenen"). De package
gebruikt nu alleen de JSON-feed (`https://data.buienradar.nl/2.0/feed/json`).

## Endpoint

```
https://gpsgadget.buienradar.nl/data/raintext?lat={lat}&lon={lon}
```

- Geeft **platte tekst** terug (geen JSON).
- Eén regel per 5 minuten voor de komende ~2 uur.
- Regelformaat: `077|14:25` — `waarde|HH:MM`.
- `waarde` is `0–255`: `0` = droog, `255` = zware regen.
- Omrekenen naar mm/uur: `mm = 10^((waarde - 109) / 32)`.

## API

Nieuwe methode op de bestaande `Buienradar`-klasse, met direct return
(in lijn met `actualForecastForStation()`, dat ook geparametriseerd is):

```php
public function rainForecast(float $lat, float $lon): array; // array<RainForecast>
```

Gebruik:

```php
$rain = (new Buienradar)->rainForecast(52.1, 5.18);
// array<RainForecast>
```

### Gedrag

- Aparte URL-constante voor de raintext-feed.
- Een fetch-methode die de **tekst**-body ophaalt (niet `json_decode`), met
  `lat` en `lon` als querystring-parameters.
- Bij een `GuzzleException` → lege array (consistent met de bestaande
  `fetchData()`).
- Body wordt op regels gesplitst; elke geldige regel wordt geparset naar een
  `RainForecast`. Lege of malformede regels worden overgeslagen.

## Model: `RainForecast`

Zelfde patroon als `Forecast` (constructor + factory + serialisatie).

```php
class RainForecast
{
    public function __construct(
        public string $time,   // 'HH:MM', bv. '14:25'
        public int    $value,  // ruwe Buienradar-waarde, 0–255
        public float  $mm,     // mm/uur, afgerond op 2 decimalen
    ) {}

    public static function fromLine(string $line): self; // parset '077|14:25'
    public function isDry(): bool;                        // value === 0
    public function toArray(): array;
    public function toJson(): string;
    public function __toString(): string;
}
```

### mm-omrekening

```php
$mm = $value === 0 ? 0.0 : round(10 ** (($value - 109) / 32), 2);
```

### Parsing (`fromLine`)

- Split op `|` → `[waarde, tijd]`.
- `value` = `(int)` van het eerste deel; `time` = `trim()` van het tweede deel.
- mm wordt berekend uit `value` (zie boven).

## Tests (Pest)

- **Fixture** met een paar raintext-regels, geserveerd via een gemockte
  Guzzle-client (zelfde aanpak als de bestaande tests met de JSON-fixture).
- `Buienradar::rainForecast()`:
  - geeft `array<RainForecast>` met het juiste aantal items;
  - slaat lege/malformede regels over;
  - geeft lege array bij een client-fout.
- `RainForecast::fromLine()`:
  - parset `time` en `value` correct;
  - rekent `mm` correct om (incl. `0 → 0.0`);
  - `isDry()` klopt voor `value === 0` en `value > 0`.

## Niet in scope

- Geen attributie/bronvermelding (bewust weggelaten op verzoek).
- Geen "minuten tot regen"-aggregaat of andere helpers buiten `isDry()` —
  toe te voegen wanneer de consumer-app het nodig heeft (YAGNI).
- Radar-afbeeldingen en andere Buienradar-feeds vallen buiten dit ontwerp.
