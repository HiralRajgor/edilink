<?php

declare(strict_types=1);

namespace Entelix\EdiLink;

use Illuminate\Support\ServiceProvider;

class EdiLinkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/edilink.php', 'edilink');

        $this->app->singleton(EdiLink::class, function ($app) {
            $ediLink = new EdiLink();

            foreach ($app['config']->get('edilink.carriers', []) as $code => $class) {
                $ediLink->register($code, $class);
            }

            return $ediLink;
        });

        $this->app->alias(EdiLink::class, 'edilink');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/edilink.php' => config_path('edilink.php'),
            ], 'edilink-config');

            $this->commands([
                \Entelix\EdiLink\Console\EdiGenerateCommand::class,
                \Entelix\EdiLink\Console\EdiValidateCommand::class,
            ]);
        }
    }
}
