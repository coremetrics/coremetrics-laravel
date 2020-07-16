<?php

namespace Coremetrics\CoremetricsLaravel;

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
        if ($this->app->runningInConsole())
        {
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

        $this->app['events']->listen(RouteMatched::class, function(RouteMatched $event)
        {
            $event->route->middleware(RouteMiddleware::class);
        });
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton('coremetrics.collector', function()
        {
            return new Collector();
        });


        $this->app->singleton('coremetrics.agent', function()
        {
            return new Agent();
        });

        $this->app->singleton('coremetrics.agentDaemon', function ()
        {
            return new AgentDaemonCommand();
        });

        $this->commands(['coremetrics.agentDaemon']);
    }
}