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
    /** @var Application */
    private $app;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $bootTime = 0;

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
        $this->app['coremetrics.collector']->append(
            null,
            null,
            [Collector::COMPR_META_TAG => TagCollection::REQUEST_STARTED],
            microtime(true)
        );

        $this->logger->debug('EventBinder - REQUEST_STARTED');
    }

    /**
     * @return void
     */
    public function bind()
    {
        // MessageSending.
        $this->app['events']->listen(
            MessageSending::class,
            function (MessageSending $_)
            {
                $this->app['coremetrics.collector']->append(
                    null,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::MAIL_SENDING
                    ]
                );

                $this->logger->debug('EventBinder - MAIL_SENDING');
            }
        );

        // MessageSent.
        $this->app['events']->listen(
            MessageSent::class,
            function (MessageSent $_)
            {
                $this->app['coremetrics.collector']->append(
                    null,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::MAIL_SENT
                    ]
                );

                $this->logger->debug('EventBinder - MAIL_SENT');
            }
        );

        // CacheHit.
        $this->app['events']->listen(
            CacheHit::class,
            function (CacheHit $event)
            {
                $this->app['coremetrics.collector']->append(
                    $event->key,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::CACHE_HIT
                    ]
                );

                $this->logger->debug('EventBinder - CACHE_HIT');
            }
        );

        // CacheMissed.
        $this->app['events']->listen(
            CacheMissed::class,
            function (CacheMissed $event)
            {
                $this->app['coremetrics.collector']->append(
                    $event->key,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::CACHE_MISSED
                    ]
                );

                $this->logger->debug('EventBinder - CACHE_MISSED');
            }
        );

        // TransactionBeginning.
        $this->app['events']->listen(
            TransactionBeginning::class,
            function (TransactionBeginning $event)
            {
                $this->app['coremetrics.collector']->append(
                    $event->connectionName,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::TRANSACTION_BEGINNING
                    ]
                );

                $this->logger->debug('EventBinder - TRANSACTION_BEGINNING');
            }
        );

        // TransactionCommitted.
        $this->app['events']->listen(
            TransactionCommitted::class,
            function (TransactionCommitted $event)
            {
                $this->app['coremetrics.collector']->append(
                    $event->connectionName,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::TRANSACTION_COMMITED
                    ]
                );
                $this->logger->debug('EventBinder - TRANSACTION_COMMITTED');
            }
        );

        // TransactionRolledBack.
        $this->app['events']->listen(
            TransactionRolledBack::class,
            function (TransactionRolledBack $event)
            {
                $this->app['coremetrics.collector']->append(
                    $event->connectionName,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::TRANSACTION_ROLLED_BACK
                    ]
                );

                $this->logger->debug('EventBinder - TRANSACTION_ROLLED_BACK');
            }
        );

        // QueryExecuted.
        $this->app['events']->listen(
            QueryExecuted::class,
            function (QueryExecuted $event)
            {
                $this->app['coremetrics.collector']->append(
                    $event->sql,
                    round($event->time, Collector::PRECISION),
                    [
                        Collector::COMPR_META_TAG => TagCollection::QUERY
                    ]
                );

                $this->logger->debug('EventBinder - QUERY');
            }
        );

        // CommandExecuted.
        // TODO(david): is this even valid? PHPStorm is flagging it as not existing but that might
        //  just be because I only have whatever gets installed based on the package's composer.json.
        $this->app['events']->listen(
            CommandExecuted::class,
            function (CommandExecuted $event)
            {
                $this->app['coremetrics.collector']->append(
                    $event->command,
                    round($event->time, Collector::PRECISION),
                    [
                        Collector::COMPR_META_TAG => TagCollection::REDIS_COMMAND
                    ]
                );

                $this->logger->debug('EventBinder - REDIS_COMMAND');
            }
        );

        // MessageLogged.
        $this->app['events']->listen(
            MessageLogged::class,
            function (MessageLogged $event)
            {
                if (Str::startsWith($event->message, 'COREMETRICS ->')) {
                    return;
                }

                $this->app['coremetrics.collector']->append(
                    $event->level,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::MSG_LOGGED
                    ]
                );

                // we don't debug MSG_LOGGED to avoid circular calls with the laravel logger
                // $this->logger->debug('EventBinder - MSG_LOGGED');
            }
        );

        // RequestHandled.
        $this->app['events']->listen(
            RequestHandled::class,
            function (RequestHandled $event)
            {
                $route = $event->request->route();

                // Generate and set the process name.
                // NOTE(david): We would have no route in cases like getting a static file.
                $name = empty($route) ? null : $route->getActionName();

                if ($name === 'Closure' || empty($name)) {
                    $name = $event->request->method() . '@' . ($route ? $route->uri() : $event->request->path());
                }

                $this->app['coremetrics.collector']->setProcessName($name);

                // Generate and set the route information.
                $this->app['coremetrics.collector']->setRouteInformation(
                    [
                        'name' => empty($route) ? null : $route->getName(),
                        'uri' => empty($route) ? null : $route->uri(),
                        'action' => empty($route) ? null : $route->getActionName(),
                    ]
                );

                // Append the event.
                $this->app['coremetrics.collector']->append(
                    null,
                    $event->response->getStatusCode(),
                    [
                        Collector::COMPR_META_TAG => TagCollection::REQUEST_HANDLED
                    ]
                );

                $this->logger->debug('EventBinder - REQUEST_HANDLED');
            }
        );

        // RouteMatched.
        $this->app['events']->listen(
            RouteMatched::class,
            function (RouteMatched $_)
            {
                $this->app['coremetrics.collector']->append(
                    null,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::REQUEST_ROUTE_MATCHED
                    ]
                );

                $this->logger->debug('EventBinder - REQUEST_ROUTE_MATCHED');
            }
        );

        // Terminating.
        $this->app->terminating(
            function ()
            {
                $this->app['coremetrics.collector']->append(
                    null,
                    null,
                    [
                        Collector::COMPR_META_TAG => TagCollection::APP_TERMINATING
                    ]
                );

                $this->app['coremetrics.collector']->flushBuffer();
                $this->app['coremetrics.connectionManager']->close();

                $this->logger->debug('EventBinder - APP_TERMINATING');
            }
        );
    }
}
