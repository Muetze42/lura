<?php

namespace NormanHuth\ConsoleApp\Console\Commands;

use NormanHuth\ConsoleApp\LuraCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class CacheClearCommand extends LuraCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush the application cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->cache->getStore()->flush();

        $this->info('Application cache cleared successfully.');

        return SymfonyCommand::SUCCESS;
    }
}
