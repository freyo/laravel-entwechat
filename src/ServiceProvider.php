<?php

namespace Freyo\LaravelEntWechat;

use EntWeChat\Foundation\Application as EntWeChatApplication;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * 延迟加载.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Boot the provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
    }

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__.'/config.php');

        if ($this->app instanceof LaravelApplication) {
            if ($this->app->runningInConsole()) {
                $this->publishes([
                    $source => config_path('entwechat.php'),
                ]);
            }
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('entwechat');
        }

        $this->mergeConfigFrom($source, 'entwechat');
    }

    /**
     * Register the provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(EntWeChatApplication::class, function ($laravelApp) {
            $app = new EntWeChatApplication(config('entwechat'));
            if (config('entwechat.use_laravel_cache')) {
                $app->cache = new CacheBridge();
            }
            $app->server->setRequest($laravelApp['request']);

            return $app;
        });
    }

    /**
     * 提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return ['wechat', EntWeChatApplication::class];
    }

    /**
     * Get config value by key.
     *
     * @return \Illuminate\Config\Repository
     */
    private function config()
    {
        return $this->app['config'];
    }
}
