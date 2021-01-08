<?php

namespace Coremetrics\CoremetricsLaravel;

class Config
{

    /**
     * @return string
     */
    public function getAgentServerUri(): string
    {
        // https://reactphp.org/socket/#server
        // 192.168.0.1:8080
        // unix:///tmp/server.sock

        return $this->getAgentLocationUri();
    }

    /**
     * @return string
     */
    public function getAgentLocationUri(): string
    {
        return '127.0.0.1:8089';
    }

    /**
     * @return string
     */
    public function getRemoteApiUrl(): string
    {
        $channelToken = config('coremetrics.channel-token', 'invalid-channel-token');

        return "http://coremetrics-server.test/api/metrics/{$channelToken}/application";
    }

    /**
     * @return int
     */
    public function getAgentTimerSeconds(): int
    {
        return 3;
    }

    /**
     * @return string
     */
    public function getAgentDaemonCommandLine(): string
    {
        return 'php ' . base_path() . '/artisan cm:daemon:start > /dev/null 2>/dev/null &';
    }
}