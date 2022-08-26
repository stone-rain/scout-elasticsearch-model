<?php

namespace ScoutElasticModel;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use ScoutElasticModel\Console\ElasticModelUpdateCommand;

class ElasticServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/scout_elastic.php' => config_path('scout_elastic.php'),
        ]);
        $this->commands([
            ElasticModelUpdateCommand::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app
            ->singleton('elastic.client', function () {
                $config = Config::get('elastic.client');

                return ClientBuilder::fromConfig($config);
            });
        $this->app->singleton('es.facade', function () {
            return new FacadeModel();
        });
    }
}
