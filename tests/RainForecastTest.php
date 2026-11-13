<?php

use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\RainForecast;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

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
    $mock = new MockHandler([new ConnectException(
        'boom',
        new Request('GET', 'raintext')
    )]);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    expect((new Buienradar($client))->rainForecast(52.1, 5.18))->toBe([]);
});

it('keeps only lines in the raintext format', function () {
    $body = "000|14:25\n<html>\n077|14:30\n999|14:35\n12|14:40\n255|14:45\nNot found";

    $rain = fakeRainBuienradar($body)->rainForecast(52.1, 5.18);

    expect(array_map(fn (RainForecast $moment) => $moment->time, $rain))
        ->toBe(['14:25', '14:30', '14:45']);
});

it('returns an empty array for a body that is not raintext', function () {
    $body = '<html><body>Onderhoud</body></html>';

    expect(fakeRainBuienradar($body)->rainForecast(52.1, 5.18))->toBe([]);
});
