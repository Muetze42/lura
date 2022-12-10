<?php

namespace Lura\Console;


use Illuminate\Filesystem\FilesystemManager;
use Lura\Traits\FilesystemTrait;
use Lura\Traits\StingTrait;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Illuminate\Contracts\Filesystem\Filesystem;

class Command extends BaseCommand
{
    use StingTrait, FilesystemTrait;

    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    protected static string $composer;
    protected static array $luraConfig = [];
    protected static FilesystemManager $filesystemManager;
    protected static Filesystem $appDisk;
    protected static null|Filesystem $altAppDisk = null;
    protected static string $missingCustomAppPath = '';

    /**
     * @var bool
     */
    protected static bool $onlyCustom;

    /**
     * @param string|null $name
     */
    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->setup();
    }

    /**
     * @return void
     */
    protected function setup(): void
    {
        static::setComposer();
        static::setAppComposerSettings();
        static::setFilesystem();
        static::setDisks();
    }

    protected static function getOptions(string $type): array
    {
        $directories = static::$appDisk->directories($type);
        $filter = data_get(static::$luraConfig, 'ignore-lura-scripts', []);
        $directories = array_diff($directories, $filter);
        if (static::$altAppDisk) {
            $directories = array_merge($directories, static::$altAppDisk->directories($type));
        }

        $array = array_map('basename', array_unique($directories));
        sort($array, SORT_NATURAL | SORT_FLAG_CASE);

        return $array;
    }

    /**
     * @return void
     */
    protected static function setDisks(): void
    {
        $appPath = data_get(static::$luraConfig, 'custom-app-path');
        static::$onlyCustom = data_get(static::$luraConfig, 'use-only-custom-app-path', false);

        if ($appPath && !is_dir($appPath)) {
            static::$missingCustomAppPath = $appPath;
        }

        if ($appPath && !static::$onlyCustom && is_dir($appPath)) {
            static::$altAppDisk = static::$filesystemManager->build([
                'driver' => 'local',
                'root'   => $appPath,
            ]);
        }

        static::$appDisk = $appPath && static::$onlyCustom && is_dir($appPath) ? static::$filesystemManager->build([
            'driver' => 'local',
            'root'   => $appPath,
        ]) : static::$filesystemManager->disk('app');
    }

    /**
     * @return void
     */
    protected static function setAppComposerSettings(): void
    {
        $command = static::composer('config --global home');

        $process = Process::fromShellCommandline($command);
        $process->run(function ($type, $line) {
            if ($type == 'out') {
                $line = trim($line);

                $configFile = trim($line, '/\\').'/lura.json';

                if (file_exists($configFile)) {
                    $array = json_decode(file_get_contents($configFile), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        static::$luraConfig = $array;
                    }
                }
            }
        });
    }

    /**
     * Set the composer command for the environment.
     */
    protected static function setComposer()
    {
        static::$composer = static::getComposer();
    }

    protected function customPathNotExist(OutputInterface $output): void
    {
        if (static::$missingCustomAppPath) {
            $output->writeln('<error>Configured custom-app-path `'.static::$missingCustomAppPath.'` in global composer config.json does not exist.</error>');
        }
    }
}
