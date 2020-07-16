<?php

namespace Coremetrics\CoremetricsLaravel;

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
        $agent = new Agent();

        $agent->listen();

        $this->info('The Coremetrics daemon is listening on ' . $agent->connectionAddress);

        $agent->loop();
    }
}