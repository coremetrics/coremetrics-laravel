<?php

namespace Coremetrics\CoremetricsLaravel;

use Coremetrics\CoremetricsLaravel\Collector\Collector;
use Coremetrics\CoremetricsLaravel\Collector\CollectorConnectionManager;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Route;
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

        $eventBinder = new EventBinder($this->app);
        $eventBinder->bind();
        $eventBinder->booting();

        /**
         * @var $httpKernel Kernel
         */
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
        $this->app->singleton('coremetrics.connectionManager', static function () {
            return new CollectorConnectionManager();
        });

        $this->app->singleton('coremetrics.collector', static function (Application $app) {
            return new Collector($app->make('coremetrics.connectionManager'));
        });


        $this->app->singleton('coremetrics.agent', static function () {
            return new Agent();
        });

        $this->app->singleton('coremetrics.agentDaemon', static function () {
            return new AgentDaemonCommand();
        });

        $this->commands(['coremetrics.agentDaemon']);
    }
}