<?php

namespace Coremetrics\CoremetricsLaravel\Collector;

use Exception;
use Log;
use Socket\Raw\Factory;
use Socket\Raw\Socket;
use Socket\Raw\Exception as SocketException;
use Throwable;

class CollectorConnectionManager
{

    /**
     * @var Socket
     */
    protected $connection;

    /**
     * @var string
     */
    protected $connectionAddress;

    /**
     * @param string $connectionAddress
     */
    public function __construct($connectionAddress = '127.0.0.1:8089')
    {
        $this->connectionAddress = $connectionAddress;
    }

    /**
     * @param string $json
     * @return int
     */
    public function write(string $json)
    {
        $connection = $this->getConnection();

        \Log::info('write');

        $connection->write($json . "\n");
    }

    /**
     * @return void
     */
    public function close()
    {
        $this->getConnection()->close();
    }

    /**
     * @return Socket
     */
    protected function getConnection(): Socket
    {
        if ($this->connection) {
            return $this->connection;
        }

        try {
            return $this->createConnection();
        } catch (SocketException $socketException) {

            if ($socketException->getCode() == SOCKET_ECONNREFUSED) {
                return $this->retryCreateConnection(10);
            } else {
                return $this->retryCreateConnection(3);
            }
        }
    }

    /**
     * @param $times
     * @param int $delay
     * @return Socket
     */
    protected function retryCreateConnection($times, $delay = 250): Socket
    {
        \Log::info('retryCreateConnection - exec ARTISAN');

        exec('php ' . base_path() . '/artisan coremetrics:daemon > /dev/null 2>/dev/null &');

        \Log::info('retryCreateConnection - exec');

        try {
            return retry($times, function() {

                \Log::info('retryCreateConnection');

                return $this->createConnection();
            }, $delay);
        } catch (Throwable $throwable) {
            Log::error($throwable);
        }
    }

    /**
     * @return Socket
     */
    protected function createConnection(): Socket
    {
        $factory = new Factory();

        return $this->connection = $factory->createClient($this->connectionAddress);
    }
}