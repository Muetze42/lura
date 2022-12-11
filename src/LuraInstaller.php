<?php

namespace NormanHuth\ConsoleApp;

abstract class LuraInstaller
{
    /**
     * Execute the installer console command.
     *
     * @param mixed|\NormanHuth\ConsoleApp\LuraCommand $command
     * @return int
     */
    abstract public function runLura(mixed $command): int;
}
