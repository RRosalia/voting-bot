<?php

namespace App\Providers;

use App\Core\WebDriver;
use Illuminate\Support\ServiceProvider;

class WebDriverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(WebDriver::class, function(){
            return WebDriver::instantiate(
                $this->app['config']->get('webdriver.host'),
                $this->app['config']->get('webdriver.proxy'),
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
