<?php

namespace Coremetrics\CoremetricsLaravel;

use Coremetrics\CoremetricsLaravel\Collector\Collector;
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
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use Illuminate\Redis\Events\CommandExecuted;

class EventBinder
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $bootTime = 0;

    /**
     * EventBinder constructor.
     * @param Application $app
     * @param LoggerInterface $logger
     */
    public function __construct(Application $app, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    /**
     * @return mixed
     */
    public function booting()
    {
        // dd((LARAVEL_START - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
        // -----> booting end
        $this->app['coremetrics.collector']->append(null, null, [Collector::COMPR_META_TAG => TagCollection::REQUEST_STARTED], microtime(true));
        $this->logger->debug('EventBinder - REQUEST_STARTED');
    }

    /**
     * @return void
     */
    public function bind()
    {
        $this->app['events']->listen(MessageSending::class, function (MessageSending $event) {
            $this->app['coremetrics.collector']->append(null, null, [
                Collector::COMPR_META_TAG => TagCollection::MAIL_SENDING
            ]);
            $this->logger->debug('EventBinder - MAIL_SENDING');
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event) {
            $this->app['coremetrics.collector']->append(null, null, [
                Collector::COMPR_META_TAG => TagCollection::MAIL_SENT
            ]);
            $this->logger->debug('EventBinder - MAIL_SENT');
        });

        $this->app['events']->listen(CacheHit::class, function (CacheHit $event) {
            $this->app['coremetrics.collector']->append($event->key, null, [
                Collector::COMPR_META_TAG => TagCollection::CACHE_HIT
            ]);
            $this->logger->debug('EventBinder - CACHE_HIT');
        });

        $this->app['events']->listen(CacheMissed::class, function (CacheMissed $event) {
            $this->app['coremetrics.collector']->append($event->key, null, [
                Collector::COMPR_META_TAG => TagCollection::CACHE_MISSED
            ]);
            $this->logger->debug('EventBinder - CACHE_MISSED');
        });

        $this->app['events']->listen(TransactionBeginning::class, function (TransactionBeginning $event) {
            $this->app['coremetrics.collector']->append($event->connectionName, null, [
                Collector::COMPR_META_TAG => TagCollection::TRANSACTION_BEGINNING
            ]);
            $this->logger->debug('EventBinder - TRANSACTION_BEGINNING');
        });

        $this->app['events']->listen(TransactionCommitted::class, function (TransactionCommitted $event) {
            $this->app['coremetrics.collector']->append($event->connectionName, null, [
                Collector::COMPR_META_TAG => TagCollection::TRANSACTION_COMMITED
            ]);
            $this->logger->debug('EventBinder - TRANSACTION_COMMITED');
        });

        $this->app['events']->listen(TransactionRolledBack::class, function (TransactionRolledBack $event) {
            $this->app['coremetrics.collector']->append($event->connectionName, null, [
                Collector::COMPR_META_TAG => TagCollection::TRANSACTION_ROLLED_BACK
            ]);
            $this->logger->debug('EventBinder - TRANSACTION_ROLLED_BACK');
        });

        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $event) {
            $this->app['coremetrics.collector']->append($event->sql, round($event->time, Collector::PRECISION), [
                Collector::COMPR_META_TAG => TagCollection::QUERY]
            );
            $this->logger->debug('EventBinder - QUERY');
        });

        $this->app['events']->listen(CommandExecuted::class, function (CommandExecuted $event) {
            $this->app['coremetrics.collector']->append($event->command, round($event->time, Collector::PRECISION), [
                    Collector::COMPR_META_TAG => TagCollection::REDIS_COMMAND]
            );
            $this->logger->debug('EventBinder - REDIS_COMMAND');
        });

        $this->app['events']->listen(MessageLogged::class, function (MessageLogged $event) {

            if (Str::startsWith($event->message, 'COREMETRICS ->')) {
                return;
            }

            $this->app['coremetrics.collector']->append($event->level, null, [
                Collector::COMPR_META_TAG => TagCollection::MSG_LOGGED
            ]);

            // we don't debug MSG_LOGGED to avoid circular calls with the laravel logger
            // $this->logger->debug('EventBinder - MSG_LOGGED');
        });

        $this->app['events']->listen(RequestHandled::class, function (RequestHandled $event) {
            $route = $event->request->route();
            $name = empty($route) ?: $route->getActionName();

            if ($name === 'Closure' || empty($name)) {
                $name = $event->request->method() . '@' . ($route ? $route->uri() : $event->request->path());
            }

            $this->app['coremetrics.collector']->setProcessName($name);
            $this->app['coremetrics.collector']->append(null, $event->response->getStatusCode(), [
                Collector::COMPR_META_TAG => TagCollection::REQUEST_HANDLED
            ]);

            $this->logger->debug('EventBinder - REQUEST_HANDLED');
        });

        $this->app['events']->listen(RouteMatched::class, function (RouteMatched $event) {
            $this->app['coremetrics.collector']->append(null, null, [
                Collector::COMPR_META_TAG => TagCollection::REQUEST_ROUTE_MATCHED
            ]);
            $this->logger->debug('EventBinder - REQUEST_ROUTE_MATCHED');
        });

        $this->app->terminating(function () {
            $this->app['coremetrics.collector']->append(null, null, [
                Collector::COMPR_META_TAG => TagCollection::APP_TERMINATING
            ]);
            $this->app['coremetrics.collector']->flushBuffer();
            $this->app['coremetrics.connectionManager']->close();

            $this->logger->debug('EventBinder - APP_TERMINATING');
        });
    }
}