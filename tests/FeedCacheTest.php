<?php

use Baspa\Buienradar\Buienradar;
use Baspa\Buienradar\Enum\MeasuringStation;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

function feedResponse(): Response
{
    return new Response(200, [], (string) file_get_contents(__DIR__.'/Fixtures/buienradar.json'));
}

function cachingBuienradar(MockHandler $mock, int $cacheSeconds = 60): Buienradar
{
    return new Buienradar(new Client(['handler' => HandlerStack::create($mock)]), $cacheSeconds);
}

it('downloads the feed once for several calls on the same instance', function () {
    $mock = new MockHandler([feedResponse(), feedResponse()]);
    $buienradar = cachingBuienradar($mock);

    $buienradar->forecast()->report();
    $buienradar->forecast()->shortTerm();
    $buienradar->actualForecastForStation(MeasuringStation::VOLKEL);
    $buienradar->stations();
    $buienradar->sunrise();

    expect($mock->count())->toBe(1);
});

it('downloads the feed again after refresh()', function () {
    $mock = new MockHandler([feedResponse(), feedResponse()]);
    $buienradar = cachingBuienradar($mock);

    $buienradar->forecast();
    $buienradar->refresh()->forecast();

    expect($mock->count())->toBe(0);
});

it('does not cache when the cache time is zero', function () {
    $mock = new MockHandler([feedResponse(), feedResponse()]);
    $buienradar = cachingBuienradar($mock, 0);

    $buienradar->forecast();
    $buienradar->forecast();

    expect($mock->count())->toBe(0);
});

it('does not cache a failed download', function () {
    $mock = new MockHandler([
        new ConnectException('boom', new Request('GET', 'feed')),
        feedResponse(),
    ]);
    $buienradar = cachingBuienradar($mock);

    expect($buienradar->forecast()->get())->toBe([])
        ->and($buienradar->forecast()->get())->toHaveKey('fivedayforecast');
});
