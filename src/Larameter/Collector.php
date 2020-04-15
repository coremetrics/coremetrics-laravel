<?php

namespace Larameter;

use Socket\Raw\Factory;

class Collector
{

    /**
     * Precision
     */
    const PRECISION = 2;

    /**
     * @var \Socket\Raw\Socket
     */
    protected $connection;

    /**
     * @var string
     */
    protected $connectionAddress;

    /**
     * @var array
     */
    protected $buffer = [];

    /**
     * @var integer
     */
    protected $lastMicrotime = LARAVEL_START;

    /**
     * @var int
     */
    protected $totalDuration = 0;

    /**
     * @var string
     */
    protected $processName;

    /**
     * @param string $connectionAddress
     */
    public function __construct($connectionAddress = '127.0.0.1:8089')
    {
        $this->connectionAddress = $connectionAddress;
    }

    /**
     * @param $processName
     */
    public function setProcessName($processName)
    {
        $this->processName = $processName;
    }

    /**
     * @param $key
     * @param $value
     * @param array $meta
     */
    public function prepend($key, $value, array $meta = [], $microtime = null)
    {
        array_unshift($this->buffer, $this->format($key, $value, $meta, $microtime, LARAVEL_START));
    }

    /**
     * @param $key
     * @param $value
     * @param array $meta
     * @param null $microtime
     */
    public function append($key, $value, array $meta = [], $microtime = null)
    {
        $this->buffer[] = $this->format($key, $value, $meta, $microtime);
    }

    /**
     * @param $key
     * @param $value
     * @param array $meta
     * @param null $now
     * @param null $lastMicrotime
     * @return array
     */
    protected function format($key, $value, array $meta = [], $now = null, $lastMicrotime = null)
    {
        if ( ! $now)
        {
            $now = microtime(true);
        }

        if ( ! $lastMicrotime)
        {
            $lastMicrotime = $this->lastMicrotime;
        }

        $diff = $now - $lastMicrotime;
        $this->lastMicrotime = $now;

        $this->totalDuration += ($diff * 1000);

        return ['k' => $key, 'v' => $value, 'm' => $meta, 'd' => round($diff * 1000, self::PRECISION)];
    }

    /**
     * @return void
     */
    public function flushBuffer()
    {
        $json = json_encode([
            't' => round(LARAVEL_START * 1000),
            's' => round($this->totalDuration, self::PRECISION),
            'n' => $this->processName,
            'l' => $this->buffer
        ]);

        $this->buffer = [];

        $this->getConnection()->write($json . "\n");
    }

    /**
     * @return void
     */
    public function close()
    {
        $this->getConnection()->close();
    }

    /**
     * @return \Socket\Raw\Socket
     */
    protected function getConnection()
    {
        if ($this->connection)
        {
            return $this->connection;
        }

        $factory = new Factory();

        return $this->connection = $factory->createClient($this->connectionAddress);
    }
}