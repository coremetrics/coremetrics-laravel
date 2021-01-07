<?php

namespace Coremetrics\CoremetricsLaravel;

class Config
{
    public function getAgentServerUri(): string
    {
        // https://reactphp.org/socket/#server
        // 192.168.0.1:8080
        // unix:///tmp/server.sock

        return $this->getAgentLocationUri();
    }

    public function getAgentLocationUri(): string
    {
        return '127.0.0.1:8089';
    }

    public function getRemoteApiUrl(): string
    {
        return 'http://coremetrics-app.test/input';
    }

    public function getAgentTimerSeconds(): int
    {
        return 3;
    }

    public function getAgentDaemonCommandLine(): string
    {
        return 'php ' . base_path() . '/artisan coremetrics:daemon > /dev/null 2>/dev/null &';
    }
}