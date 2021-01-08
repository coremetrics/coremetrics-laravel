<?php

namespace Coremetrics\CoremetricsLaravel;

use Coremetrics\CoremetricsLaravel\Collector\Collector;
use Coremetrics\CoremetricsLaravel\Collector\CollectorConnectionManager;
use Coremetrics\CoremetricsLaravel\Console\Commands\AgentDaemonCommand;
use Coremetrics\CoremetricsLaravel\Loggers\LaravelLogger;
use Coremetrics\CoremetricsLaravel\Providers\ScheduleServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;

class CoremetricsLaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $eventBinder = new EventBinder($this->app, $this->app->make('coremetrics.logger'));
        $eventBinder->bind();
        $eventBinder->booting();

        /** @var $httpKernel Kernel */
        $httpKernel = $this->app->make(Kernel::class);
        $httpKernel->prependMiddleware(AppMiddleware::class);

        $this->app['events']->listen(RouteMatched::class, function (RouteMatched $event) {
            $event->route->middleware(RouteMiddleware::class);
        });
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton('coremetrics.logger', static function (Application $app) {
            return new LaravelLogger($app);
        });

        $this->app->singleton('coremetrics.config', static function () {
            return new Config();
        });

        $this->app->singleton('coremetrics.connectionManager', static function (Application $app) {
            return new CollectorConnectionManager($app->make('coremetrics.config'), $app->make('coremetrics.logger'));
        });

        $this->app->singleton('coremetrics.collector', static function (Application $app) {
            return new Collector($app->make('coremetrics.connectionManager'));
        });

        $this->app->singleton('coremetrics.agent', static function (Application $app) {
            return new Agent($app->make('coremetrics.config'), $app->make('coremetrics.logger'));
        });

        $this->app->singleton('coremetrics.agentDaemon', static function () {
            return new AgentDaemonCommand();
        });

        $this->app->register(ScheduleServiceProvider::class);

        $this->commands(['coremetrics.agentDaemon']);
    }
}