<?php

namespace Coremetrics\CoremetricsLaravel;

use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;
use React\Socket\Server;

class Agent
{

    /**
     * @var Server
     */
    protected $socket;

    /**
     * @var Factory
     */
    protected $loop;

    /**
     * @var array
     */
    protected $buffer = [];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;

        $this->loop = Factory::create();
        $this->socket = new Server($this->config->getAgentServerUri(), $this->loop);
    }

    /**
     * @return void
     */
    public function listen()
    {
        $this->socket->on('connection', function (ConnectionInterface $connection) {
            $connection->on('data', function ($data) use ($connection) {
                $this->buffer[] = json_decode($data, true);
                $this->logger->debug('Agent received data', [
                    'data' =>$data
                ]);
            });

            $this->logger->info('The Coremetrics daemon is listening on ' . $this->config->getAgentServerUri());
        });

        $total = microtime(true);

        $this->loop->addPeriodicTimer($this->config->getAgentTimerSeconds(), function () use($total) {

            $this->logger->debug('Agent - addPeriodicTimer', [
                'total_agent_lifetime' => (microtime(true) - $total)
            ]);

            $buffer = $this->buffer;
            $this->buffer = [];

            if ($buffer) {
                $this->postData($buffer);
            }
        });
    }

    /**
     * @param $buffer
     */
    protected function postData($buffer)
    {
        $data = [
            'data' => $buffer
        ];

        $requestBody = json_encode($data);

        $this->logger->debug('Agent - postData', [
            'item_count' => count($buffer),
            'size' => strlen($requestBody),
        ]);

        $ch = curl_init($this->config->getRemoteApiUrl());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($requestBody)
        ]);

        $result = curl_exec($ch);

        $this->logger->debug('Agent - postData response', [
            'response' => $result,
        ]);
    }

    /**
     * @return void
     */
    public function loop()
    {
        $this->loop->run();
    }
}