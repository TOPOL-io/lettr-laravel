<?php

use Illuminate\Support\Facades\Mail;
use Lettr\Laravel\Transport\LettrTransportFactory;

it('can create lettr mail transport', function () {
    Mail::extend('lettr', function (array $config = []) {
        return new LettrTransportFactory(app('lettr'), $config['options'] ?? []);
    });

    $transport = Mail::mailer('lettr')->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(LettrTransportFactory::class);
});

