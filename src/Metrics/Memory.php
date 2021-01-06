<?php

namespace Coremetrics\CoremetricsLaravel\Metrics;

class Memory extends BaseMetric
{
    public function handle()
    {
        $output = $this->executeProcess("free -m | grep Mem | awk '{print $2, $7}'");

        $data = explode(' ', $output);

        return [
            'total' => $data[0],
            'avail' => $data[1],
        ];
    }
}
