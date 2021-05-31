<?php

namespace Coremetrics\CoremetricsLaravel;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use React\Socket\ConnectionInterface;
use React\Socket\Server;

class Agent
{
    /** @var Server */
    private $socket;

    /** @var Factory */
    private $loop;

    /** @var array */
    private $buffer = [];

    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $logger;

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
        $this->socket->on(
            'connection',
            function (ConnectionInterface $connection)
            {
                $connection->on(
                    'data',
                    function ($data)
                    {
                        $this->buffer[] = json_decode($data, true);
                        $this->logger->debug(
                            'Agent received data',
                            [
                                'data' => $data
                            ]
                        );
                    }
                );

                $this->logger->debug('The Coremetrics daemon is listening on ' . $this->config->getAgentServerUri());
            }
        );

        $total = microtime(true);

        $this->loop->addPeriodicTimer(
            $this->config->getAgentTimerSeconds(),
            function () use ($total)
            {
                $this->logger->debug(
                    'Agent - addPeriodicTimer',
                    [
                        'total_agent_lifetime' => (microtime(true) - $total)
                    ]
                );

                $buffer = $this->buffer;
                $this->buffer = [];

                if ($buffer) {
                    $this->postData($buffer);
                }
            }
        );
    }

    /**
     * @return void
     */
    public function loop()
    {
        $this->loop->run();
    }

    /**
     * @return void
     */
    private function postData(array $buffer)
    {
        $data = [
            'data' => $buffer
        ];

        $requestBody = json_encode($data);

        $serverUrl = $this->config->getRemoteApiUrl();

        $this->logger->debug(
            'Agent - postData',
            [
                'item_count' => count($buffer),
                'size' => strlen($requestBody),
                'url' => $serverUrl,
            ]
        );

        (new Browser($this->loop))->post(
            $serverUrl,
            [
                'Content-Type' => 'application/json',
                // NOTE(david): VERY IMPORTANT! The server needs to know what version the payload is to correctly
                // interpret it, as we are likely to change it in the future.
                'X-Coremetrics-Payload-Version' => Config::PAYLOAD_VERSION,
                'User-Agent' => 'CoreMetrics/' . Config::PAYLOAD_VERSION,
            ],
            $requestBody
        )->then(function (ResponseInterface $response) {
            $this->logger->debug(
                'Agent - postData response',
                [
                    'response' => $response->getBody()->getContents(),
                    'errors' => '',
                    'response_detail' => $response->getStatusCode(),
                ]
            );
        }, function (ResponseException $responseException) {
            $this->logger->debug(
                'Agent - postData response',
                [
                    'response' => $responseException->getResponse()->getBody()->getContents(),
                    'errors' => $responseException->getMessage(),
                    'response_detail' => $responseException->getResponse()->getStatusCode(),
                ]
            );
        })->done();
    }
}
