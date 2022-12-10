<?php

namespace Lura\Traits;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemManager;

trait FilesystemTrait
{
    /**
     * @return void
     */
    protected static function setFilesystem(): void
    {
        $container = new Container;
        $container->instance('app', $container);
        $container['config'] = new Repository(require rootDir('config/filesystems.php'));

        /** @var Application $container */
        static::$filesystemManager = new FilesystemManager($container);
    }
}
