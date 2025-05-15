<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable;

use Illuminate\Support\ServiceProvider;

class InertiaDatatableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration and assets
        $this->publishes([
            __DIR__.'/../config/inertia-datatable.php' => config_path('inertia-datatable.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/inertia-datatable/dist'),
            __DIR__.'/../resources/js' => resource_path('js/vendor/inertia-datatable'),
            __DIR__.'/../tailwind.config.js' => base_path('vendor/arkhas/inertia-datatable/tailwind.config.js'),
        ], 'assets');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'inertia-datatable');

        // Publish translations
        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/inertia-datatable'),
        ], 'translations');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/inertia-datatable.php',
            'inertia-datatable'
        );

        $this->app->bind(InertiaDatatable::class, function () {
            return new InertiaDatatable();
        });
    }
}
