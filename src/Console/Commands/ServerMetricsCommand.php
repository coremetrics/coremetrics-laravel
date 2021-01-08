<?php

namespace Coremetrics\CoremetricsLaravel\Console\Commands;

use Coremetrics\CoremetricsLaravel\Metrics\Cpu;
use Coremetrics\CoremetricsLaravel\Metrics\Disk;
use Coremetrics\CoremetricsLaravel\Metrics\Memory;
use Illuminate\Console\Command;

class ServerMetricsCommand extends Command
{
    protected $name = 'cm:metrics:report';

    public function handle()
    {
        $data = $this->executeChecks();

        $this->transmitData($data);
    }

    private function transmitData($data)
    {
        $requestBody = json_encode(
            [
                'payload' => $data,
                'timestamp' => time(),
            ]
        );

        $ch = curl_init($this->getPostUrl());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($requestBody)
            ]
        );

        $result = curl_exec($ch);
    }

    private function getPostUrl(): string
    {
        return config('coremetrics.url') . config('coremetrics.token');
    }

    private function executeChecks(): array
    {
        return [
            'cpu' => (new Cpu())->handle(),
            'disk' => (new Disk())->handle(),
            'memory' => (new Memory())->handle(),
        ];
    }
}