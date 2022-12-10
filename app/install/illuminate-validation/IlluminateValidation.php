<?php

namespace install;

use Lura\Service\Installer;

class IlluminateValidation extends Installer
{
    public static function composerPackages(): array
    {
        return [
            'illuminate/filesystem',
            'illuminate/translation',
            'illuminate/validation',
        ];
    }
}
