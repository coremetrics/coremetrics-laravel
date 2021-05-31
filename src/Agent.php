<?php

namespace Coremetrics\CoremetricsLaravel;

use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;
use React\Socket\Server;
use WyriHaximus\React\PSR3\Stdio\StdioLogger;

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

    public function __construct(Config $config)
    {
        $this->config = $config;

        $this->loop = Factory::create();
        $this->logger = StdioLogger::create($this->loop)->withNewLine(true);
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

                $this->logger->debug('Received connection from ' . $connection->getRemoteAddress());
            }
        );

        $this->logger->debug('The Coremetrics daemon is listening on ' . $this->config->getAgentServerUri());

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

        $ch = curl_init($serverUrl);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($requestBody),
                // NOTE(david): VERY IMPORTANT! The server needs to know what version the payload is to correctly
                // interpret it, as we are likely to change it in the future.
                'X-Coremetrics-Payload-Version: ' . Config::PAYLOAD_VERSION,
            ]
        );

        $result = curl_exec($ch);

        $errors = curl_error($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->logger->debug(
            'Agent - postData response',
            [
                'response' => $result,
                'errors' => $errors,
                'response_detail' => $response,
            ]
        );
    }
}
