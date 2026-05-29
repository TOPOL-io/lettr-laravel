<?php

use Lettr\Laravel\Facades\Lettr;
use Lettr\Services\CampaignService;

it('resolves the campaign service from the facade', function () {
    expect(Lettr::campaigns())->toBeInstanceOf(CampaignService::class);
});

it('resolves the campaign service via the manager magic property', function () {
    expect(app('lettr')->campaigns)->toBeInstanceOf(CampaignService::class);
});

it('returns the same campaign service instance on repeated calls', function () {
    expect(Lettr::campaigns())->toBe(Lettr::campaigns());
});
