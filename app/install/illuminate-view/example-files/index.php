<?php

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

require __DIR__.'/../../vendor/autoload.php';

function view(string $view, array $data = []): string
{
    $viewsPaths = [
        __DIR__.'/views',
    ];
    $compiledPath = __DIR__.'/compiled';

    $filesystem = new Filesystem;
    $eventDispatcher = new Dispatcher(new Container);

    $viewResolver = new EngineResolver;
    $bladeCompiler = new BladeCompiler($filesystem, $compiledPath);

    $viewResolver->register('blade', function () use ($bladeCompiler) {
        return new CompilerEngine($bladeCompiler);
    });

    $viewResolver->register('php', function () use ($filesystem) {
        return new PhpEngine($filesystem);
    });

    $viewFinder = new FileViewFinder($filesystem, $viewsPaths);
    $viewFactory = new Factory($viewResolver, $viewFinder, $eventDispatcher);

    return $viewFactory->make($view, $data)->render();
}

echo view('example');
