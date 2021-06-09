<?php

namespace Coremetrics\CoremetricsLaravel;

class Config
{
    const PAYLOAD_VERSION = '0.0.1';

    public function getAgentServerUri(): string
    {
        return $this->getAgentLocationUri();
    }

    public function getAgentLocationUri(): string
    {
        return '127.0.0.1:' . $this->getAgentPort();
    }

    public function getRemoteApiUrl(): string
    {
        $channelToken = config('coremetrics.token', 'invalid-channel-token');

        $baseUrl = config('coremetrics.server.base_url');

        return "{$baseUrl}/api/metrics/{$channelToken}/application";
    }

    public function getAgentTimerSeconds(): int
    {
        return 3;
    }

    public function getAgentDaemonCommandLine(): string
    {
        return 'php ' . base_path() . '/artisan cm:daemon:start > /dev/null 2>/dev/null &';
    }

    private function getAgentPort(): int
    {
        return config('coremetrics.port', 8089);
    }
}
