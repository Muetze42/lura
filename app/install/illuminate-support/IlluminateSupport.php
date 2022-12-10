<?php

namespace install;

use Lura\Service\Installer;

class IlluminateSupport extends Installer
{
    public static function composerPackages(): array
    {
        return[
            'illuminate/support',
        ];
    }

    public static function composerAfterInstallMessage(): string
    {
        return '<comment>Visit</comment> <info>https://laravel.com/docs/helpers</info> <comment>to learn what methods are available</comment>';
    }
}
