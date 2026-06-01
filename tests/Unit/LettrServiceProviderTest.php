<?php

use Lettr\Laravel\Exceptions\ApiKeyIsMissing;
use Lettr\Laravel\Facades\Lettr;
use Lettr\Laravel\LettrManager;
use Lettr\Laravel\LettrServiceProvider;
use Lettr\Lettr as LettrClient;

it('can resolve lettr manager from container', function () {
    $manager = app('lettr');

    expect($manager)->toBeInstanceOf(LettrManager::class);
});

it('can resolve raw lettr client from container', function () {
    $client = app(LettrClient::class);

    expect($client)->toBeInstanceOf(LettrClient::class);
});

it('can use lettr facade', function () {
    expect(Lettr::getFacadeRoot())->toBeInstanceOf(LettrManager::class);
});

it('passes the lettr-laravel user-agent suffix to the client', function () {
    /** @var LettrClient $client */
    $client = app(LettrClient::class);

    // The SDK keeps the transporter and its computed User-Agent private, so we
    // reflect in to assert the binding actually forwards the suffix. This guards
    // against the silent no-op where an unsupported argument is dropped.
    $transporter = (function () {
        return $this->client;
    })->call($client);

    $userAgent = (function () {
        return $this->userAgent;
    })->call($transporter);

    expect($userAgent)->toContain('lettr-laravel/'.LettrServiceProvider::VERSION);
});

it('throws exception when api key is missing', function () {
    config()->set('lettr.api_key', null);
    config()->set('services.lettr.key', null);

    // The exception is thrown when trying to use the SDK, not when resolving the manager
    app('lettr')->sdk();
})->throws(ApiKeyIsMissing::class);
