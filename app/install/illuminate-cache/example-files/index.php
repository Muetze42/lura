<?php

require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

/* @var Application $app */
$app = new Container;
$app->singleton('files', function(){
    return new Filesystem();
});
$app->singleton('config', function(){
    return [
        'cache.default' => 'files',
        'cache.stores.files' => [
            'driver' => 'file',
            'path' => __DIR__.'/cache',
        ]
    ];
});

$cacheManager = new CacheManager($app);
$cache = $cacheManager->driver();

$cache->put('test', \Illuminate\Support\Str::random(12));
try {
    echo $cache->get('test');
} catch (\Psr\SimpleCache\InvalidArgumentException $exception) {
    echo $exception;
}
