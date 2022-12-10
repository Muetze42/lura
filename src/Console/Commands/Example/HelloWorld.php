<?php

namespace NormanHuth\ConsoleApp\Console\Commands\Example;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class HelloWorld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hello:world';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a example hello world command';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->comment('Hello World');

        return SymfonyCommand::SUCCESS;
    }
}
