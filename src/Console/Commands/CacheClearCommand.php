<?php

namespace NormanHuth\ConsoleApp\Console\Commands;

use NormanHuth\ConsoleApp\LuraCommand;

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
    protected $description = 'Flush the application cache.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->cache->getStore()->flush();

        $this->info('Application cache cleared successfully.');
    }
}
