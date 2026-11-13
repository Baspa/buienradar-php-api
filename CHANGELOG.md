# Changelog

All notable changes to `buienradar-php-api` will be documented in this file.

## v2.2.0 - 2026-11-13

### What's new

- **Sun and stations** (#50): `sunrise()` / `sunset()` return a `DateTimeImmutable` in `Europe/Amsterdam`, `stations()` returns every current measurement, and `nearestStation(float $lat, float $lon)` returns the station closest to a coordinate.
- **Feed reuse** (#53): the JSON feed is downloaded once and reused by the same instance for 60 seconds, so `forecast()`, station lookups and sun times no longer each make a request. `refresh()` forces a new download; `new Buienradar(cacheSeconds: 0)` turns reuse off.
- **Horst** (#48): new `MeasuringStation::HORST`.

```php
$buienradar = new Buienradar();

$buienradar->sunrise();                     // 2026-11-13 07:55:00 Europe/Amsterdam
$buienradar->nearestStation(52.1, 5.18);    // Meetstation De Bilt
$buienradar->refresh()->forecast();         // download the feed again

```
### Fixes

- **Failures no longer look like data** (#49): non-2xx responses, HTML or other non-feed bodies, invalid or malformed JSON now return an empty result (or `null`) instead of fake "dry" rain moments, a `TypeError` or a PHP warning. The default client times out after 10 s (5 s to connect).
- **Measuring stations** (#48): the enum matches the live feed again. `GILZE_RIJEN` and `HOUTRIBDIJK` are the correctly spelled cases; `GILZE_RIJEEN` and `HOULTRIBDIJK` keep working as deprecated aliases. `ARCEN` is deprecated: Buienradar no longer publishes it, so it returns `null`.
- **`forDay()`** (#46): days outside 0–4 throw an `InvalidArgumentException` instead of returning an empty `Forecast`.
- **Guzzle 7** (#51): installs next to Guzzle 7 again.

### Changed behaviour

- `forDay()` with a day below 0 or above 4 now throws `InvalidArgumentException`.
- One instance reuses the feed for up to 60 seconds. If you keep an instance alive and need every call to hit Buienradar, pass `cacheSeconds: 0`.
- `guzzlehttp/guzzle` is now `^7.15.2 || ^8.0.1`. Every older release has security advisories (latest: CVE-2026-69245/69246), and Composer 2.9+ refuses to install them by default.
- The rain forecast is fetched from `https://gps.buienradar.nl/getrr.php`, the URL Buienradar documents, instead of the underlying `gpsgadget.buienradar.nl` host.

### Docs and maintenance

- README: Buienradar's terms of use (free for non-commercial use on your own website or intranet, attribution required), error handling, caching, and a local contributing note (#47).
- CI is green again on PHP 8.2–8.5, and `composer test` works without a coverage driver (#52).
- Dependabot minor/patch updates are squash-merged once their checks pass (#45).

**Full Changelog**: https://github.com/Baspa/buienradar-php-api/compare/v2.1.0...v2.2.0

## v2.1.0 - 2026-06-19

### What's new

Adds a second Buienradar feed: the short-term **rain forecast** (raintext).

- New `Buienradar::rainForecast(float $lat, float $lon): array` — returns `array<RainForecast>` with the precipitation forecast for the next ~2 hours (per 5 minutes) at a coordinate.
- New `RainForecast` value object: `time`, `value` (0–255), `mm` (mm/hour), and `isDry()`.

```php
$rain = (new Buienradar)->rainForecast(52.1, 5.18);
foreach ($rain as $moment) {
    echo $moment->time;    // '14:25'
    echo $moment->mm;      // mm per hour
    echo $moment->isDry();
}


```
**Full Changelog**: https://github.com/Baspa/buienradar-php-api/compare/v2.0.1...v2.1.0

## v2.0.0 - 2026-06-15

### What's Changed

* Bump dependabot/fetch-metadata from 1.6.0 to 2.0.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/1
* Bump aglipanci/laravel-pint-action from 2.3.1 to 2.4 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/2
* Bump dependabot/fetch-metadata from 2.0.0 to 2.1.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/3
* Bump dependabot/fetch-metadata from 2.1.0 to 2.2.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/4
* Update pestphp/pest requirement from ^2.15 to ^3.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/5
* Update phpstan/phpstan requirement from ^1.10 to ^2.1 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/7
* Bump dependabot/fetch-metadata from 2.2.0 to 2.3.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/8
* Bump aglipanci/laravel-pint-action from 2.4 to 2.5 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/9
* Bump dependabot/fetch-metadata from 2.3.0 to 2.4.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/10
* Bump aglipanci/laravel-pint-action from 2.5 to 2.6 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/12
* Bump stefanzweifel/git-auto-commit-action from 5 to 7 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/14
* Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/17
* Bump dependabot/fetch-metadata from 2.5.0 to 3.0.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/18
* Bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 by @dependabot[bot] in https://github.com/Baspa/buienradar-php-api/pull/19
* 2.x: green CI + Buienradar PascalCase API support by @Baspa in https://github.com/Baspa/buienradar-php-api/pull/23

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/Baspa/buienradar-php-api/pull/1
* @Baspa made their first contribution in https://github.com/Baspa/buienradar-php-api/pull/23

**Full Changelog**: https://github.com/Baspa/buienradar-php-api/compare/v1.0.0...v2.0.0

## v1.0.0 - 2024-03-16

**Full Changelog**: https://github.com/Baspa/buienradar-php-api/commits/v1.0.0
