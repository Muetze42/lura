<?php

namespace NormanHuth\Lura\Console\Commands;

use NormanHuth\Lura\LuraCommand;

class ConfigFileCommand extends LuraCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the path to the local config file.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Local Lura config file:');
        $this->line($this->userConfigFile);
    }
}
