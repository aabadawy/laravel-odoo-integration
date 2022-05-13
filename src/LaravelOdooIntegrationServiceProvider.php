<?php

namespace Aabadawy\LaravelOdooIntegration;

use Aabadawy\LaravelOdooIntegration\Commands\MakeOdooModule;
use \Illuminate\Support\ServiceProvider;

class LaravelOdooIntegrationServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/odoo-integration.php' => config_path('odoo-integration.php'),
        ]);
        


        if($this->app->runningInConsole())
            $this->commands([
                MakeOdooModule::class
            ]);
    }
}
