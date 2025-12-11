<?php

namespace Lettr\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Lettr\Lettr;
use Lettr\Laravel\Exceptions\ApiKeyIsMissing;
use Lettr\Laravel\Transport\LettrTransportFactory;

class LettrServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPublishing();

        Mail::extend('lettr', function (array $config = []) {
            return new LettrTransportFactory($this->app['lettr'], $config['options'] ?? []);
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->configure();
        $this->bindLettrClient();
    }

    /**
     * Setup the configuration for Lettr.
     */
    protected function configure(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/lettr.php',
            'lettr'
        );
    }

    /**
     * Bind the Lettr Client.
     */
    protected function bindLettrClient(): void
    {
        $this->app->singleton('lettr', static function (): Lettr {
            $apiKey = config('lettr.api_key') ?? config('services.lettr.key');

            if (! is_string($apiKey)) {
                throw ApiKeyIsMissing::create();
            }

            return Lettr::client($apiKey);
        });

        $this->app->alias('lettr', Lettr::class);
    }

    /**
     * Register the package's publishable assets.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lettr.php' => $this->app->configPath('lettr.php'),
            ], 'lettr-config');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'lettr',
            Lettr::class,
        ];
    }
}

