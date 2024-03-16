# Buienradar PHP API

[![Total Downloads](https://img.shields.io/packagist/dt/baspa/buienradar-php-api.svg?style=flat-square)](https://packagist.org/packages/baspa/buienradar-php-api)
[![Tests](https://github.com/baspa/buienradar-php-api/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/baspa/buienradar-php-api/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/baspa/buienradar-php-api/actions/workflows/phpstan.yml/badge.svg?branch=main)](https://github.com/baspa/buienradar-php-api/actions/workflows/phpstan.yml)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/baspa/buienradar-php-api)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/baspa/buienradar-php-api)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/baspa/buienradar-php-api.svg?style=flat-square)](https://packagist.org/packages/baspa/buienradar-php-api)

This framework-agnostic PHP package provides a fluent syntax to interact with the Buienradar API.

## Installation

You can install the package via composer:

```bash
composer require baspa/buienradar-php-api
```

## Usage

### Initialization

Create an instance of the `Buienradar` class:

```php
use Baspa\Buienradar\Buienradar;

$buienradar = new Buienradar();
```

### Get the actual forecast report

```php
$forecast = $buienradar->forecast()->report();
```

### Get the short-term forecast

```php
$forecast = $buienradar->forecast()->shortTerm();
```

### Get the long-term forecast

```php
$forecast = $buienradar->forecast()->longTerm();
```

### Get the forecast for the upcoming 5 days

```php
$forecast = $buienradar->forecast()->forFiveDays();
```

### Get the forecast for one specific day

```php
$forecast = $buienradar->forecast()->forDay(1);
```

### Get the actual forecast for a specific measurement station

Choose either one of the following measuring stations:

-   Meetstation Arcen
-   Meetstation Arnhem
-   Meetstation Berkhout
-   Meetstation De Bilt
-   Meetstation Den Helder
-   Meetstation Eindhoven
-   Meetstation Ell
-   Meetstation Gilze Rijen
-   Meetstation Goes
-   Meetstation Groenlo-Hupsel
-   Meetstation Groningen
-   Meetstation Heino
-   Meetstation Herwijnen
-   Meetstation Hoek van Holland
-   Meetstation Hoogeveen
-   Meetstation Hoorn Terschelling
-   Meetstation Houtribdijk
-   Meetstation IJmuiden
-   Meetstation Lauwersoog
-   Meetstation Leeuwarden
-   Meetstation Lelystad
-   Meetstation Lopik-Cabauw
-   Meetstation Maastricht
-   Meetstation Marknesse
-   Meetstation Nieuw Beerta
-   Meetstation Rotterdam
-   Meetstation Rotterdam Geulhaven
-   Meetstation Schiphol
-   Meetstation Stavoren
-   Meetstation Texelhors
-   Meetstation Twente
-   Meetstation Vlieland
-   Meetstation Vlissingen
-   Meetstation Volkel
-   Meetstation Voorschoten
-   Meetstation Westdorpe
-   Meetstation Wijdenes
-   Meetstation Wijk aan Zee
-   Meetstation Woensdrecht
-   Meetstation Zeeplatform F-3

For example, to get the forecast for the Volkel station, use:

```php
use Baspa\Buienradar\Enum\MeasuringStation;

$forecast = $buienradar->actualForecastForStation(MeasuringStation::VOLKEL);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Baspa](https://github.com/Baspa)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
