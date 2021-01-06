<?php

namespace Coremetrics\CoremetricsLaravel\Metrics;

class Cpu extends BaseMetric
{
    public function handle()
    {
        // loadavg
        $output = $this->executeProcess("cat /proc/loadavg | awk '{print $1, $2, $3}'");
        $load = explode(' ', $output);

        // processors
        // $process = Process::fromShellCommandline("cat /proc/cpuinfo | grep processor | wc -l");
        $cores = $this->executeProcess("nproc");

        return [
            '1m' => $load[0],
            '5m' => $load[1],
            '15m' => $load[2],
            'cores' => $cores,
        ];
    }
}
