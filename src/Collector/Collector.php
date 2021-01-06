<?php

namespace Coremetrics\CoremetricsLaravel\Collector;

class Collector
{

    /**
     * Precision
     */
    const PRECISION = 2;

    /**
     * Compression keys
     */
    const COMPR_EVENT_TIMESTAMP = 't';
    const COMPR_TOTAL_DURATION = 's';
    const COMPR_PROCESS_NAME = 'n';
    const COMPR_PROCESS_EVENT_BUFFER = 'l';
    const COMPR_KEY = 'k';
    const COMPR_VALUE = 'v';
    const COMPR_DURATION = 'd';
    const COMPR_META = 'm';
    const COMPR_META_TAG = 'mt';

    /**
     * @var CollectorConnectionManager
     */
    protected $connectionManager;

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
     * @param CollectorConnectionManager $connectionManager
     */
    public function __construct(CollectorConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
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
        if (!$now) {
            $now = microtime(true);
        }

        if (!$lastMicrotime) {
            $lastMicrotime = $this->lastMicrotime;
        }

        $diff = $now - $lastMicrotime;
        $this->lastMicrotime = $now;

        $this->totalDuration += ($diff * 1000);

        return [
            self::COMPR_KEY => $key,
            self::COMPR_VALUE => $value,
            self::COMPR_META => $meta,
            self::COMPR_DURATION => round($diff * 1000, self::PRECISION)
            // TODO(david): do we want to set the duration like this?
            //  It seems like it might be nicer to send up the actual microtime and we can handle that on the server as we want?
        ];
    }

    /**
     * @return void
     */
    public function flushBuffer()
    {
        $json = json_encode([
            self::COMPR_EVENT_TIMESTAMP => round(LARAVEL_START * 1000),
            self::COMPR_TOTAL_DURATION => round($this->totalDuration, self::PRECISION),
            self::COMPR_PROCESS_NAME => $this->processName,
            self::COMPR_PROCESS_EVENT_BUFFER => $this->buffer
        ]);

        $this->buffer = [];

        $this->connectionManager->write($json);
    }
}
