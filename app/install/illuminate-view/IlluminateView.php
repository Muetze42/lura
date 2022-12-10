<?php

namespace install;

use Lura\Service\Installer;

class IlluminateView extends Installer
{
    public static function composerPackages(): array
    {
        return [
            'illuminate/container',
            'illuminate/contracts',
            'illuminate/events',
            'illuminate/filesystem',
            'illuminate/view',
        ];
    }
}
