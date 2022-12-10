<?php

require __DIR__.'/../../vendor/autoload.php';

/**
 * Use Filesystem simple
 */
$filesystem = new \Illuminate\Filesystem\Filesystem;
if (!$filesystem->isDirectory(__DIR__.'/filesystem')) {
    $filesystem->makeDirectory(__DIR__.'/filesystem');
}

/*************************
 * Storage (Recommended) *
 ************************/

/**
 * Create a new filesystem manager instance.
 */
$container = new \Illuminate\Container\Container;
$container->instance('app', $container);
$config = require __DIR__.'/config.php';
$container['config'] = new Illuminate\Config\Repository($config);
/** @var \Illuminate\Contracts\Foundation\Application $container */
$filesystemManager = new \Illuminate\Filesystem\FilesystemManager($container);

/**
 * Build an on-demand disk.
 * https://laravel.com/docs/filesystem#on-demand-disks
 */
$disk = $filesystemManager->build([
    'driver' => 'local',
    'root'   => __DIR__.'/storage'
]);
$disk->put(\Illuminate\Support\Str::random(6).'/'.\Illuminate\Support\Str::random().'.txt', \Illuminate\Support\Str::random());
