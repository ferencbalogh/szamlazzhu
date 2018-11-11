<?php
namespace FerencBalogh\Szamlazz;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([$this->configPath() => config_path('szamlazz.php')], 'config');
    }

    /**
     * Congfiguration file location
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '/../config/szamlazz.php';
    }
}
