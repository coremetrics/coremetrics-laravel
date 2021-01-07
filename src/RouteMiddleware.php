<?php

namespace Coremetrics\CoremetricsLaravel;

use Closure;
use Coremetrics\CoremetricsLaravel\Collector\Collector;
use \Illuminate\Contracts\Foundation\Application;

class RouteMiddleware
{
    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->app['coremetrics.collector']->append(
            null,
            null,
            [Collector::COMPR_META_TAG => TagCollection::MIDDLEWARE_END]
        );

        return $next($request);
    }
}