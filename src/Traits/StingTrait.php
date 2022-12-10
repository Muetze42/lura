<?php

namespace Lura\Traits;

trait StingTrait
{
    /**
     * @param string $command
     * @return string
     */
    protected static function composer(string $command): string
    {
        return trim(static::$composer).' '.trim($command);
    }

    /**
     * Get the composer command for the environment.
     */
    public static function getComposer(): string
    {
        $composerPath = getcwd().'/composer.phar';

        return file_exists($composerPath) ? '"'.PHP_BINARY.'" '.$composerPath : 'composer';
    }
}
