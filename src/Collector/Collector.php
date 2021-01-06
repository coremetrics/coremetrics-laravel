<?php

namespace Coremetrics\CoremetricsLaravel\Collector;

class Collector
{
    /** Precision */
    const PRECISION = 2;

    /** Compression keys */
    const COMPR_EVENT_TIMESTAMP = 't';
    const COMPR_TOTAL_DURATION = 's';
    const COMPR_PROCESS_NAME = 'n';
    const COMPR_ROUTE_INFORMATION = 'ri';
    const COMPR_PROCESS_EVENT_BUFFER = 'l';
    const COMPR_KEY = 'k';
    const COMPR_VALUE = 'v';
    const COMPR_DURATION = 'd';
    const COMPR_META = 'm';
    const COMPR_META_TAG = 'mt';

    /** @var CollectorConnectionManager */
    private $connectionManager;

    /** @var array */
    private $buffer = [];

    /** @var float */
    private $lastMicrotime = LARAVEL_START;

    /** @var float */
    private $totalDuration = 0;

    /** @var string */
    private $processName;

    /** @var array|null */
    private $routeInformation;

    public function __construct(CollectorConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function setProcessName(string $processName)
    {
        $this->processName = $processName;
    }

    public function setRouteInformation(array $routeInformation)
    {
        $this->routeInformation = $routeInformation;
    }

    /**
     * @param string|null $key
     * @param mixed|null $value
     * @param array $meta
     * @param float|null $microtime
     */
    public function prepend(string $key = null, $value = null, array $meta = [], float $microtime = null)
    {
        array_unshift($this->buffer, $this->format($key, $value, $meta, $microtime, LARAVEL_START));
    }

    /**
     * @param string|null $key
     * @param mixed|null $value
     * @param array $meta
     * @param float|null $microtime
     *
     * @return void
     */
    public function append(string $key = null, $value = null, array $meta = [], float $microtime = null)
    {
        $this->buffer[] = $this->format($key, $value, $meta, $microtime);
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
            self::COMPR_ROUTE_INFORMATION => $this->routeInformation,
            self::COMPR_PROCESS_EVENT_BUFFER => $this->buffer
        ]);

        $this->buffer = [];

        $this->connectionManager->write($json);
    }

    /**
     * @param string|null $key
     * @param mixed|null $value
     * @param array $meta
     * @param float|null $now
     * @param float|null $lastMicrotime
     * @return array
     */
    private function format(string $key = null, $value = null, array $meta = [], float $now = null, float $lastMicrotime = null): array
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

        // TODO(david): do we want to set the duration like this?
        //  It seems like it might be nicer to send up the actual microtime and we can handle that on the server as we want?
        return [
            self::COMPR_KEY => $key,
            self::COMPR_VALUE => $value,
            self::COMPR_META => $meta,
            self::COMPR_DURATION => round($diff * 1000, self::PRECISION)
        ];
    }
}
