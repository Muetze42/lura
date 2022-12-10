<?php

namespace install;

use Lura\Service\Installer;

class IlluminateFilesystem extends Installer
{
    public static function composerPackages(): array
    {
        return [
            'illuminate/config',
            'illuminate/container',
            'illuminate/filesystem',
            'league/flysystem',
        ];
    }
}
