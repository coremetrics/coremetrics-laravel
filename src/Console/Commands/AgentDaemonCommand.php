<?php

namespace Coremetrics\CoremetricsLaravel\Console\Commands;

use Illuminate\Console\Command;

class AgentDaemonCommand extends Command
{
    protected $name = 'cm:daemon:start';

    public function handle()
    {
        /** @var Agent $agent */
        $agent = app()->make('coremetrics.agent');

        $agent->listen();
        $agent->loop();
    }
}
