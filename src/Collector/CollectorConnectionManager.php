<?php

namespace Coremetrics\CoremetricsLaravel\Collector;

use Coremetrics\CoremetricsLaravel\Config;
use Psr\Log\LoggerInterface;
use Socket\Raw\Factory;
use Socket\Raw\Socket;
use Socket\Raw\Exception as SocketException;
use Throwable;

class CollectorConnectionManager
{
    /** @var Socket */
    protected $connection;

    /** @var Config */
    protected $config;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return void
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
            }

            return $this->retryCreateConnection(3);
        }
    }

    protected function retryCreateConnection(int $times, int $delay = 250): Socket
    {
        $this->logger->debug('CollectorConnectionManager - launching daemon');

        $cmd = $this->config->getAgentDaemonCommandLine();
        exec($cmd);

        try {
            return retry(
                $times,
                function ()
                {
                    $this->logger->debug('CollectorConnectionManager - retryCreateConnection');

                    return $this->createConnection();
                },
                $delay
            );
        } catch (Throwable $throwable) {
            $this->logger->debug('CollectorConnectionManager - createConnection:error' . $throwable->getMessage());
        }

        // TODO(david): we're making an assumption here that the retry works, and we don't return anything
        //  if it doesn't. Given the typing, I'm fairly certain that means we just crash, which doesn't
        //  feel very robust. Probably want to look into shoring up this code a bit.
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