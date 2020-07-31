<?php

namespace Coremetrics\CoremetricsLaravel;

use Illuminate\Foundation\Application;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Routing\Events\RouteMatched;

class EventBinder
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var int
     */
    protected $bootTime = 0;

    /**
     * EventBinder constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return mixed
     */
    public function booting()
    {
        // dd((LARAVEL_START - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
        // -----> booting end
        $this->app['coremetrics.collector']->append(null, null, ['t' => TagCollection::REQUEST_STARTED],
            microtime(true));
    }

    /**
     * @return void
     */
    public function bind()
    {
        $this->app['events']->listen(MessageSending::class, function (MessageSending $event) {
            $this->app['coremetrics.collector']->append($event->key, null, ['t' => TagCollection::MAIL_SENDING]);
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event) {
            $this->app['coremetrics.collector']->append($event->key, null, ['t' => TagCollection::MAIL_SENT]);
        });

        $this->app['events']->listen(CacheHit::class, function (CacheHit $event) {
            $this->app['coremetrics.collector']->append($event->key, null, ['t' => TagCollection::CACHE_HIT]);
        });

        $this->app['events']->listen(CacheMissed::class, function (CacheMissed $event) {
            $this->app['coremetrics.collector']->append($event->key, null, ['t' => TagCollection::CACHE_MISSED]);
        });

        $this->app['events']->listen(TransactionBeginning::class, function (TransactionBeginning $event) {
            $this->app['coremetrics.collector']->append($event->connectionName, null,
                ['t' => TagCollection::TRANSACTION_BEGINNING]);
        });

        $this->app['events']->listen(TransactionCommitted::class, function (TransactionCommitted $event) {
            $this->app['coremetrics.collector']->append($event->connectionName, null,
                ['t' => TagCollection::TRANSACTION_COMMITED]);
        });

        $this->app['events']->listen(TransactionRolledBack::class, function (TransactionRolledBack $event) {
            $this->app['coremetrics.collector']->append($event->connectionName, null,
                ['t' => TagCollection::TRANSACTION_ROLLED_BACK]);
        });

        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $event) {
            $this->app['coremetrics.collector']->append($event->sql, round($event->time, Collector::PRECISION),
                ['t' => TagCollection::QUERY]);
        });

        $this->app['events']->listen(MessageLogged::class, function (MessageLogged $event) {
            $this->app['coremetrics.collector']->append($event->level, null, ['t' => TagCollection::MSG_LOGGED]);
        });

        $this->app['events']->listen(RequestHandled::class, function (RequestHandled $event) {
            $route = $event->request->route();
            $name = empty($route) ?: $route->getActionName();

            if ($name == 'Closure' || empty($name)) {
                $name = $event->request->method() . '@' . $event->request->path();
            }

            $this->app['coremetrics.collector']->setProcessName($name);
            $this->app['coremetrics.collector']->append(null, $event->response->getStatusCode(),
                ['t' => TagCollection::REQUEST_HANDLED]);
        });

        $this->app['events']->listen(RouteMatched::class, function (RouteMatched $event) {
            $this->app['coremetrics.collector']->append(null, null, ['t' => TagCollection::REQUEST_ROUTE_MATCHED]);
        });

        $this->app->terminating(function () {
            $this->app['coremetrics.collector']->append(null, null, ['t' => TagCollection::APP_TERMINATING]);
            $this->app['coremetrics.collector']->flushBuffer();
            $this->app['coremetrics.connectionManager']->close();

            \Log::info('terminating');
        });
    }
}