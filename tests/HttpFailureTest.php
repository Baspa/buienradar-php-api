<?php

use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function buienradarWithoutHttpErrors(Response $response): Buienradar
{
    return new Buienradar(new Client([
        'handler' => HandlerStack::create(new MockHandler([$response])),
        'http_errors' => false,
    ]));
}

function clientOf(Buienradar $buienradar): mixed
{
    return (new ReflectionProperty(Buienradar::class, 'client'))->getValue($buienradar);
}

it('gives the default client a timeout', function () {
    $client = clientOf(new Buienradar);

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->getConfig('timeout'))->toBe(10)
        ->and($client->getConfig('connect_timeout'))->toBe(5);
});

it('uses an injected client as-is', function () {
    $client = new Client;

    expect(clientOf(new Buienradar($client)))->toBe($client);
});

it('returns an empty rain forecast for a non-2xx response', function (Response $response) {
    expect(buienradarWithoutHttpErrors($response)->rainForecast(52.1, 5.18))->toBe([]);
})->with([
    'out-of-range coordinates' => fn () => new Response(404, [], 'Not found: location must be inside the Netherlands or Belgium.'),
    'server error with a raintext body' => fn () => new Response(500, [], "000|14:25\n077|14:30"),
]);

it('returns an empty forecast when the JSON feed fails', function (Response $response) {
    expect(buienradarWithoutHttpErrors($response)->forecast()->get())->toBe([]);
})->with([
    'non-2xx' => fn () => new Response(503, [], (string) file_get_contents(__DIR__.'/Fixtures/buienradar.json')),
    'invalid JSON' => fn () => new Response(200, [], '<html><body>Onderhoud</body></html>'),
    'scalar JSON' => fn () => new Response(200, [], '"Service unavailable"'),
]);

it('returns no station forecast when the JSON feed responds with a non-2xx status', function () {
    $response = new Response(503, [], (string) file_get_contents(__DIR__.'/Fixtures/buienradar.json'));

    expect(buienradarWithoutHttpErrors($response)->actualForecastForStation(MeasuringStation::VOLKEL))->toBeNull();
});
