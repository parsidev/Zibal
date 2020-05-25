<?php

namespace Parsidev\Zibal;

use Illuminate\Support\ServiceProvider;

class ZibalServiceProvider extends ServiceProvider {

    protected $defer = false;

    public function boot() {
        $this->publishes([
            __DIR__ . '/../../config/zibal.php' => config_path('zibal.php'),
        ]);
    }

    public function register() {
        $this->app->singleton('zibal_parsidev', function($app) {
            $config = config('zibal');
            return new Zibal($config);
        });
    }

    public function provides() {
        return ['zibal_parsidev'];
    }

}
