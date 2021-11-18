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
        $this->app->register(WebDriver::class, function(){

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
