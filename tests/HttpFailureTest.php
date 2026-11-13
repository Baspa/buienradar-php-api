<?php

use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
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

it('returns an empty forecast when the forecast key is not an object', function () {
    expect(buienradarWithoutHttpErrors(new Response(200, [], '{"forecast":"x"}'))->forecast()->get())->toBe([]);
});

it('returns no station forecast when the measurements are malformed', function (string $body) {
    expect(buienradarWithoutHttpErrors(new Response(200, [], $body))->actualForecastForStation(MeasuringStation::VOLKEL))->toBeNull();
})->with([
    'measurements is a string' => '{"actual":{"stationmeasurements":"x"}}',
    'measurement is a string' => '{"actual":{"stationmeasurements":["x"]}}',
]);

it('reads the body even when middleware already consumed the stream', function () {
    $stack = HandlerStack::create(new MockHandler([new Response(200, [], "000|14:25\n077|14:30")]));
    $stack->push(Middleware::mapResponse(function ($response) {
        $response->getBody()->getContents();

        return $response;
    }));

    expect((new Buienradar(new Client(['handler' => $stack])))->rainForecast(52.1, 5.18))->toHaveCount(2);
});

it('drops raintext lines with an impossible time', function () {
    $rain = buienradarWithoutHttpErrors(new Response(200, [], "000|14:25\n000|99:99\n000|24:00"))->rainForecast(52.1, 5.18);

    expect($rain)->toHaveCount(1)
        ->and($rain[0]->time)->toBe('14:25');
});
