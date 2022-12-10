<?php

namespace install;

use Lura\Service\Installer;

class IlluminateCache extends Installer
{
    public static function composerPackages(): array
    {
        return [
            'illuminate/filesystem',
            'illuminate/cache',
            'illuminate/container',
        ];
    }
}
