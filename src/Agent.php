<?php

namespace Coremetrics\CoremetricsLaravel;

use React\EventLoop\Factory;
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
        $this->socket->on('connection', function (\React\Socket\ConnectionInterface $connection)
        {
            $connection->on('data', function ($data) use ($connection)
            {
                $this->buffer[] = json_decode($data, true);
            });
        });

        $this->loop->addPeriodicTimer(3, function ()
        {
            $buffer = $this->buffer;
            $this->buffer = [];

            if ($buffer)
            {
                $this->postData($buffer);
            }
        });
    }

    /**
     * @param $buffer
     */
    protected function postData($buffer)
    {

        var_dump($buffer);
//
//        return;

        $data = [
            'data' =>$buffer
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

        if ($result != 'OK')
        {
            var_dump($result);exit;
        }

        var_dump(count($buffer));
    }

    /**
     * @return void
     */
    public function loop()
    {
        $this->loop->run();
    }
}