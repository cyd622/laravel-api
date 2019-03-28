<?php
/**
 * Description:
 * Project: laravel_api
 * Author: Ciel (luffywang622@gmail.com)
 * Created on: 2019/03/26 11:26
 * Created by PhpStorm.
 */

namespace Cyd622\LaravelApi;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ApiServiceProvider extends LaravelServiceProvider
{
    /**
     * 服务提供者加是否延迟加载.
     *
     * @var bool
     */
    protected $defer = true; // 延迟加载服务

    /**
     * Boot the provider.
     */
    public function boot()
    {

    }

    /**
     * Setup the config.
     */
    protected function setupConfig()
    {
        // 发布配置文件到 laravel 的config 下
        $source = realpath(__DIR__ . '/config.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([$source => config_path('laravel_api.php')], 'laravel-api');
        }

        $this->mergeConfigFrom($source, 'laravel_api');
    }

    /**
     * Register the provider.
     */
    public function register()
    {
        $this->setupConfig();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        // 因为延迟加载 所以要定义 provides 函数 具体参考laravel 文档
        return ['laravel_api'];
    }
}