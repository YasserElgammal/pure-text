<?php

namespace YasserElgammal\PureText;

use Illuminate\Support\ServiceProvider;
use YasserElgammal\PureText\Services\PureTextFilterService;

class PureTextServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/badwords.php' => config_path('badwords.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/badwords.php',
            'badwords'
        );

        $this->app->singleton(PureTextFilterService::class, function () {
            return new PureTextFilterService();
        });
    }
}
