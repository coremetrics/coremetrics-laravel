<?php

namespace Coremetrics\CoremetricsLaravel\Commands;

use Coremetrics\CoremetricsLaravel\Agent;
use Illuminate\Console\Command;

class AgentDaemonCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'coremetrics:daemon';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $agent = app()->make('coremetrics.agent');

        $agent->listen();
        $agent->loop();
    }
}