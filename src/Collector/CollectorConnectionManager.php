<?php

namespace Coremetrics\CoremetricsLaravel\Collector;

use Coremetrics\CoremetricsLaravel\Config;
use Log;
use Psr\Log\LoggerInterface;
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
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CollectorConnectionManager constructor.
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param string $json
     * @return int
     */
    public function write(string $json)
    {
        $connection = $this->getConnection();
        $connection->write($json . "\n");

        $this->logger->debug('CollectorConnectionManager - write');
    }

    /**
     * @return void
     */
    public function close()
    {
        $this->getConnection()->close();

        $this->logger->debug('CollectorConnectionManager - close');
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

            $this->logger->debug('CollectorConnectionManager - ' . $socketException->getCode());

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
        $this->logger->debug('CollectorConnectionManager - launching daemon');

        $cmd = $this->config->getAgentDaemonCommandLine();
        exec($cmd);

        try {
            return retry($times, function() {

                $this->logger->debug('CollectorConnectionManager - retryCreateConnection');

                return $this->createConnection();
            }, $delay);
        } catch (Throwable $throwable) {
            $this->logger->debug('CollectorConnectionManager - createConnection:error' . $throwable->getMessage());
        }
    }

    /**
     * @return Socket
     */
    protected function createConnection(): Socket
    {
        $factory = new Factory();

        return $this->connection = $factory->createClient($this->config->getAgentLocationUri());
    }
}