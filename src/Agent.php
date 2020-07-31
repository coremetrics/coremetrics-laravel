<?php

namespace Coremetrics\CoremetricsLaravel;

use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;
use React\Socket\Server;

class Agent
{

    /**
     * @var string
     */
    public $connectionAddress;

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
     * @param string $connectionAddress
     */
    public function __construct($connectionAddress = '127.0.0.1:8089')
    {
        $this->connectionAddress = $connectionAddress;

        $this->loop = Factory::create();
        $this->socket = new Server($this->connectionAddress, $this->loop);
    }

    /**
     * @return void
     */
    public function listen()
    {
        $this->socket->on('connection', function (ConnectionInterface $connection) {
            $connection->on('data', function ($data) use ($connection) {
                \Log::info('daemon data');
                $this->buffer[] = json_decode($data, true);
            });
        });

        $total = microtime(true);

        $this->loop->addPeriodicTimer(3, function () use($total) {

            \Log::info('Daemon lifetime: ' . (microtime(true) - $total));

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

        \Log::info('daemon dump');

//        var_dump($buffer);
//
//        return;

        $data = [
            'data' => $buffer
        ];

        $data_string = json_encode($data);

        $ch = curl_init('http://coremetrics.test/input');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ]);

        $result = curl_exec($ch);

        if ($result != 'OK') {
            \Log::info('daemon dump: response '  . $result);
            exit;
        }

        \Log::info('daemon dump: count '  . count($buffer));
    }

    /**
     * @return void
     */
    public function loop()
    {
        $this->loop->run();
    }
}