<?php

namespace Lura\Traits;

use Symfony\Component\Process\Process;

trait LaravelHelpers
{
    protected string $laravelNovaKey = '';

    protected function getNovaKey(): string
    {
        $command = static::composer('config --global http-basic.nova.laravel.com.password');
        $process = Process::fromShellCommandline($command);
        $process->run(function ($type, $line) {
            if ($type == 'out') {
                $this->laravelNovaKey = trim($line);
            }
        });

        return $this->laravelNovaKey;
    }
}
