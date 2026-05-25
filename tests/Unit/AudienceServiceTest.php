<?php

use Lettr\Laravel\Facades\Lettr;
use Lettr\Services\Audience\AudienceContactService;
use Lettr\Services\Audience\AudienceListService;
use Lettr\Services\Audience\AudiencePropertyService;
use Lettr\Services\Audience\AudienceSegmentService;
use Lettr\Services\Audience\AudienceTopicService;
use Lettr\Services\AudienceService;

it('resolves the audience service from the facade', function () {
    expect(Lettr::audience())->toBeInstanceOf(AudienceService::class);
});

it('resolves the audience service via the manager magic property', function () {
    expect(app('lettr')->audience)->toBeInstanceOf(AudienceService::class);
});

it('exposes the five audience sub-services', function () {
    $audience = Lettr::audience();

    expect($audience->contacts())->toBeInstanceOf(AudienceContactService::class)
        ->and($audience->lists())->toBeInstanceOf(AudienceListService::class)
        ->and($audience->segments())->toBeInstanceOf(AudienceSegmentService::class)
        ->and($audience->topics())->toBeInstanceOf(AudienceTopicService::class)
        ->and($audience->properties())->toBeInstanceOf(AudiencePropertyService::class);
});

it('exposes the audience sub-services via magic properties', function () {
    $audience = Lettr::audience();

    expect($audience->contacts)->toBeInstanceOf(AudienceContactService::class)
        ->and($audience->lists)->toBeInstanceOf(AudienceListService::class)
        ->and($audience->segments)->toBeInstanceOf(AudienceSegmentService::class)
        ->and($audience->topics)->toBeInstanceOf(AudienceTopicService::class)
        ->and($audience->properties)->toBeInstanceOf(AudiencePropertyService::class);
});
