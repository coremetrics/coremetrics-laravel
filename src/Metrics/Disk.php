<?php

namespace Coremetrics\CoremetricsLaravel\Metrics;

class Disk extends BaseMetric
{
    public function handle()
    {
        $output = $this->executeProcess("df -BG / | grep /dev | awk '{print $2, $3, $4, $5}'");

        $data = explode(' ', $output);

        return [
            'size' => str_replace('G', '', $data[0]),
            'used' => str_replace('G', '', $data[1]),
            'avail' => str_replace('G', '', $data[2]),
            'percent' => str_replace('%', '', $data[3]),
        ];
    }
}
