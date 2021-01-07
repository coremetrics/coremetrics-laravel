<?php

namespace Coremetrics\CoremetricsLaravel;

use Closure;
use Coremetrics\CoremetricsLaravel\Collector\Collector;
use Illuminate\Foundation\Application;

class AppMiddleware
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
            [Collector::COMPR_META_TAG => TagCollection::MIDDLEWARE_START]
        );

        $this->app['coremetrics.logger']->debug('AppMiddleware - ' . TagCollection::MIDDLEWARE_START);

        return $next($request);
    }
}