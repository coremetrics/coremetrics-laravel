<?php

namespace Coremetrics\CoremetricsLaravel\Metrics;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BaseMetric
{
    public function executeProcess(string $command): string
    {
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this->removeLineFeeds($process->getOutput());
    }

    private function removeLineFeeds(string $string)
    {
        return preg_replace('/\R/', '', $string);
    }
}
