<?php

namespace Webkul\UspsShipping\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class UspsShippingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'usps');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'usps');

         //checkout page
         $this->publishes([
            __DIR__ . '/../Resources/views/shop/default/checkout/onepage/shipping.blade.php' => resource_path('themes/default/views/checkout/onepage/shipping.blade.php')
        ]);

        //checkout velocity page
        $this->publishes([
            __DIR__ . '/../Resources/views/shop/velocity/checkout/onepage/shipping.blade.php' => resource_path('themes/velocity/views/checkout/onepage/shipping.blade.php')
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/carriers.php', 'carriers'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );
    }
}
