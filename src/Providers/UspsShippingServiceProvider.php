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

        //checkout onepage page
        $this->publishes([
            __DIR__ . '/../Resources/views/shop/default/checkout/onepage/shipping.blade.php' => resource_path('themes/default/views/checkout/onepage/shipping.blade.php')
        ]);

        //checkout cart page
        $this->publishes([
            __DIR__ . '/../Resources/views/shop/default/checkout/cart/index.blade.php' => resource_path('themes/default/views/checkout/cart/index.blade.php')
        ]);

        //field type page
        $this->publishes([
            __DIR__ . '/../Resources/views/configuration/field-type.blade.php' => __DIR__ . '/../../../Admin/src/Resources/views/configuration/field-type.blade.php',
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
            dirname(__DIR__) . '/Config/carriers.php',
            'carriers'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php',
            'core'
        );
    }
}