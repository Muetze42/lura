<?php

namespace NormanHuth\ConsoleApp\Console\Commands;

use NormanHuth\ConsoleApp\LuraCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

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
    protected $description = 'Get the path to the local config file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Local Lura config file:');
        $this->line($this->userConfigFile);

        return SymfonyCommand::SUCCESS;
    }
}
