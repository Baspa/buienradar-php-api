# Raintext Rain Forecast Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `rainForecast(float $lat, float $lon)` method to the Buienradar client that returns the short-term precipitation forecast from Buienradar's raintext feed as an array of `RainForecast` objects.

**Architecture:** A new `RainForecast` value object (mirroring the existing `Forecast` model) parses one raintext line (`077|14:25`) into time/value/mm. The `Buienradar` client gets a second URL constant and a text-fetch path that hits `gpsgadget.buienradar.nl/data/raintext` with `lat`/`lon` query params, splits the body into lines, and maps each valid line through `RainForecast::fromLine()`.

**Tech Stack:** PHP 8.2+, GuzzleHTTP 7, Pest 3, PHPStan 2, Pint.

## Global Constraints

- PHP `^8.2`; only existing dependency `guzzlehttp/guzzle ^7.8` (no new deps).
- Namespace `Baspa\Buienradar\` → `src/`; tests `Baspa\Buienradar\Tests\` → `tests/`.
- Follow existing model pattern: constructor with public promoted props, `fromX()` factory, `toArray()`, `toJson()`, `__toString()`.
- Client errors (`GuzzleException`) return an empty array — never throw.
- No attribution / source-credit logic (explicitly excluded).
- mm/hour formula: `value === 0 ? 0.0 : round(10 ** (($value - 109) / 32), 2)`.
- Run `composer format` (Pint) before each commit; tests via `vendor/bin/pest`.

---

### Task 1: `RainForecast` model

**Files:**
- Create: `src/RainForecast.php`
- Test: `tests/RainForecastTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `RainForecast::__construct(string $time, int $value, float $mm)` with public promoted props `$time`, `$value`, `$mm`.
  - `RainForecast::fromLine(string $line): self` — parses `'077|14:25'`.
  - `RainForecast::isDry(): bool` — `value === 0`.
  - `RainForecast::toArray(): array{time:string,value:int,mm:float}`.
  - `RainForecast::toJson(): string`, `__toString(): string`.

- [ ] **Step 1: Write the failing test**

```php
<?php

use Baspa\Buienradar\RainForecast;

it('parses a raintext line into time, value and mm', function () {
    $rain = RainForecast::fromLine('077|14:25');

    expect($rain)->toBeInstanceOf(RainForecast::class)
        ->and($rain->time)->toBe('14:25')
        ->and($rain->value)->toBe(77)
        ->and($rain->mm)->toBe(round(10 ** ((77 - 109) / 32), 2));
});

it('reports mm as 0.0 and isDry true when value is 0', function () {
    $rain = RainForecast::fromLine('000|14:30');

    expect($rain->value)->toBe(0)
        ->and($rain->mm)->toBe(0.0)
        ->and($rain->isDry())->toBeTrue();
});

it('reports isDry false when it is raining', function () {
    expect(RainForecast::fromLine('077|14:25')->isDry())->toBeFalse();
});

it('serialises to array and json', function () {
    $rain = RainForecast::fromLine('077|14:25');

    expect($rain->toArray())->toBe(['time' => '14:25', 'value' => 77, 'mm' => $rain->mm])
        ->and($rain->toJson())->toBe(json_encode($rain->toArray()))
        ->and((string) $rain)->toBe($rain->toJson());
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/RainForecastTest.php`
Expected: FAIL — `Class "Baspa\Buienradar\RainForecast" not found`.

- [ ] **Step 3: Write minimal implementation**

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/RainForecastTest.php`
Expected: PASS (4 passed).

- [ ] **Step 5: Format and commit**

```bash
composer format
git add src/RainForecast.php tests/RainForecastTest.php
git commit -m "feat: add RainForecast model parsing raintext lines"
```

---

### Task 2: `Buienradar::rainForecast()` method + fixture

**Files:**
- Modify: `src/Buienradar.php` (add URL constant, text-fetch path, `rainForecast()` method)
- Create: `tests/Fixtures/raintext.txt`
- Modify: `tests/RainForecastTest.php` (add client-level tests)

**Interfaces:**
- Consumes: `RainForecast::fromLine()` from Task 1.
- Produces: `Buienradar::rainForecast(float $lat, float $lon): array` returning `array<int, RainForecast>`.

- [ ] **Step 1: Create the raintext fixture**

Create `tests/Fixtures/raintext.txt` with this exact content (note the blank trailing line to prove empty lines are skipped):

```
000|14:25
077|14:30
255|14:35

```

- [ ] **Step 2: Write the failing client tests**

Append to `tests/RainForecastTest.php`:

```php
use Baspa\Buienradar\Buienradar;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function fakeRainBuienradar(string $body): Buienradar
{
    $mock = new MockHandler([new Response(200, [], $body)]);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    return new Buienradar($client);
}

it('returns a rain forecast parsed from the raintext feed', function () {
    $body = (string) file_get_contents(__DIR__.'/Fixtures/raintext.txt');

    $rain = fakeRainBuienradar($body)->rainForecast(52.1, 5.18);

    expect($rain)->toBeArray()
        ->and($rain)->toHaveCount(3)
        ->and($rain[0])->toBeInstanceOf(RainForecast::class)
        ->and($rain[0]->time)->toBe('14:25')
        ->and($rain[0]->isDry())->toBeTrue()
        ->and($rain[2]->value)->toBe(255);
});

it('returns an empty array when the request fails', function () {
    $mock = new MockHandler([new \GuzzleHttp\Exception\ConnectException(
        'boom',
        new \GuzzleHttp\Psr7\Request('GET', 'raintext')
    )]);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    expect((new Buienradar($client))->rainForecast(52.1, 5.18))->toBe([]);
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `vendor/bin/pest tests/RainForecastTest.php`
Expected: FAIL — `Call to undefined method Baspa\Buienradar\Buienradar::rainForecast()`.

- [ ] **Step 4: Implement `rainForecast()` in `src/Buienradar.php`**

Add the raintext URL constant next to the existing `URL` constant (after line 12):

```php
    private const RAINTEXT_URL = 'https://gpsgadget.buienradar.nl/data/raintext';
```

Add this method to the class (e.g. after `actualForecastForStation()`):

```php
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
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/pest tests/RainForecastTest.php`
Expected: PASS (all RainForecast tests green).

- [ ] **Step 6: Run the full suite + static analysis**

Run: `vendor/bin/pest` then `vendor/bin/phpstan analyse`
Expected: all tests pass; no new PHPStan errors.

- [ ] **Step 7: Format and commit**

```bash
composer format
git add src/Buienradar.php tests/RainForecastTest.php tests/Fixtures/raintext.txt
git commit -m "feat: add rainForecast() reading Buienradar raintext feed"
```

---

### Task 3: Documentation

**Files:**
- Modify: `README.md`

**Interfaces:**
- Consumes: `Buienradar::rainForecast()` from Task 2.
- Produces: nothing (docs only).

- [ ] **Step 1: Add a usage section to README.md**

Add a section documenting the new method, matching the style of the existing examples:

````markdown
### Rain forecast (short-term precipitation)

Fetch the short-term precipitation forecast (next ~2 hours, per 5 minutes) for a
coordinate:

```php
use Baspa\Buienradar\Buienradar;

$rain = (new Buienradar)->rainForecast(52.1, 5.18);

foreach ($rain as $moment) {
    echo $moment->time;      // '14:25'
    echo $moment->value;     // 0–255 (raw Buienradar value)
    echo $moment->mm;        // mm per hour
    echo $moment->isDry();   // true when value === 0
}
```
````

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: document rainForecast() in README"
```

---

## Self-Review Notes

- **Spec coverage:** endpoint + query params (Task 2 Step 4), text-not-JSON fetch (Task 2), GuzzleException → empty array (Task 2 Step 2/4), line splitting + skip malformed/empty (Task 2 fixture + impl), `RainForecast` model with time/value/mm + `fromLine`/`isDry`/serialisation (Task 1), mm formula incl. `0 → 0.0` (Task 1), tests via mocked Guzzle (Tasks 1–2), no attribution (nothing added). All covered.
- **Type consistency:** `rainForecast(): array<int, RainForecast>`, `RainForecast::fromLine(): self`, `isDry(): bool`, `toArray(): array{time,value,mm}` — consistent across tasks.
- **Placeholders:** none.
