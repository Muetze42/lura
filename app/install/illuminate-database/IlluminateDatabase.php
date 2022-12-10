<?php

namespace install;

use Lura\Service\Installer;

class IlluminateDatabase extends Installer
{
    public static function composerPackages(): array
    {
        return [
            'illuminate/database',
        ];
    }
}
