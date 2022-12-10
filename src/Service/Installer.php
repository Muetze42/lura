<?php

namespace Lura\Service;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Installer extends Support
{
    protected static array $options;
    protected static array $composerAfterInstallMessage = [];
    protected static array $composerPackages = [];
    protected static array $publishDirectories = [];

    public function runLura(InputInterface $input, OutputInterface $output, Filesystem $appDisk, null|Filesystem $altAppDisk,array $options, array $luraConfig): int
    {
        static::setFilesystem();
        static::$filesystem = new \Illuminate\Filesystem\Filesystem;
        static::setCommand('install');
        static::$input = $input;
        static::$output = $output;
        static::$composer = static::getComposer();
        static::$luraConfig = $luraConfig;
        static::$options = $options;
        static::$appDisk = $appDisk;
        static::$altAppDisk = $altAppDisk;
        static::setTargetDisk();

        return $this->executeLura();
    }

    protected function executeLura(): int
    {
        foreach (static::$options as $option) {
            $optionStudly = Str::studly($option);
            $path = 'install/'.$option;
            $file = $path.'/'.$optionStudly.'.php';
            $exampleFiles = $path.'/example-files';

            if (static::$appDisk->directoryExists($exampleFiles) || (static::$altAppDisk && static::$altAppDisk->directoryExists($exampleFiles))) {
                static::$publishDirectories[$option] = $exampleFiles;
            }

            if (static::$altAppDisk && static::$altAppDisk->exists($file)) {
                require static::$altAppDisk->path($file);
            } else {
                require static::$appDisk->path($file);
            }

            $className = 'install\\'.$optionStudly;
            $class = new $className;
            static::$composerPackages = array_merge(static::$composerPackages, $class::composerPackages());

            if (method_exists($class, 'composerAfterInstallMessage')) {
                static::$composerAfterInstallMessage[] = $class::composerAfterInstallMessage();
            }
        }

        static::$composerPackages = array_values(array_unique(static::$composerPackages));
        static::$composerAfterInstallMessage = array_values(array_unique(static::$composerAfterInstallMessage));
        $this->composerInstall();
        $this->publishFiles();
        $this->composerAfterInstallMessages();

        return self::SUCCESS;
    }

    protected function publishFiles()
    {
        foreach (static::$publishDirectories as $option => $directory) {
            static::publishFolder($directory, 'examples/'.$option);
        }
    }

    protected function composerAfterInstallMessages()
    {
        foreach (static::$composerAfterInstallMessage as $message) {
            static::$output->writeln($message);
        }
    }

    protected function composerInstall()
    {
        $packages = array_map(function ($value) {
            return str_contains($value, ':') ? "'".$value."'" : $value;
        }, static::$composerPackages);
        $packages = implode(' ', $packages);

        static::runCommand(static::composer('require '.$packages));
    }

    //        $packages = $this->packageNames();
//        if (is_array($packages)) {
//            $packages = array_map(function ($value) {
//                return str_contains($value, ':') ? "'".$value."'" : $value;
//            }, $packages);
//            $packages = implode(' ', $packages);
//        }
//
//        static::runCommand(static::composer('require '.$packages));
//
//        if (static::$appDisk->directoryExists('example-files') || (static::$altAppDisk) && static::$altAppDisk->directoryExists('example-files')) {
//            static::publishFolder('example-files', 'examples/'.basename(static::$appDisk->path('')));
//        }
//
//        if ($this->afterMessage()) {
//            static::$output->writeln($this->afterMessage());
//        }
//
//        return static::SUCCESS;
}
