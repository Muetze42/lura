<?php

namespace Lura\Service;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use Lura\Traits\FilesystemTrait;
use Lura\Traits\StingTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Support
{
    use FilesystemTrait, StingTrait;

    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;
    protected static InputInterface $input;
    protected static OutputInterface $output;
    protected static string $composer;
    protected static Filesystem $appDisk;
    protected static null|Filesystem $altAppDisk = null;
    protected static ?Filesystem $targetDisk = null;
    protected static Filesystem $currentPathDisk;
    protected static FilesystemManager $filesystemManager;
    protected static string $name;
    protected static Command $command;
    protected static array $luraConfig;
    protected static string $tempPath = '';
    protected static \Illuminate\Filesystem\Filesystem $filesystem;
    protected static array|null $gitConfig = null;

    protected static function setNewDisks(Filesystem $appDisk, null|Filesystem $altAppDisk, string $path): void
    {
        $newAppDisk = static::$filesystemManager->build([
            'driver' => 'local',
            'root'   => trim($appDisk->path($path), '/\\'),
        ]);
        static::$appDisk = $newAppDisk;

        if ($altAppDisk) {
            $newAltAppDisk = static::$filesystemManager->build([
                'driver' => 'local',
                'root'   => trim($altAppDisk->path($path), '/\\'),
            ]);
            static::$altAppDisk = $newAltAppDisk;
        }

        static::$currentPathDisk = static::$filesystemManager->build([
            'driver' => 'local',
            'root'   => trim(getcwd(), '/\\'),
        ]);
    }

    protected static function setCommand(string $type): void
    {
        $application = new Application;
        $class = '\\Lura\\Console\\'.ucfirst($type).'Command';
        $application->add(new $class);

        static::$command = $application->find($type);
    }

    protected function runCommand(string|array $commands): void
    {
        if (is_array($commands)) {
            $commands = implode(' && ', $commands);
        }

        $commands = static::$targetDisk ? 'cd '.static::$targetDisk->path('/').' && '.$commands :
            'cd '.getcwd().' && '.$commands;

        $process = Process::fromShellCommandline($commands);
        $process->run(function ($type, $line) {
            static::$output->write('    '.$line);
        });

//        if (!$process->isSuccessful()) {
//            throw new ProcessFailedException($process);
//        }
    }

    protected static function setTargetDisk(?string $path = null): void
    {
        $path = $path != null && isset(static::$currentPathDisk) ? static::$currentPathDisk->path($path) : getcwd();

        static::$targetDisk = static::$filesystemManager->build([
            'driver' => 'local',
            'root'   => $path,
        ]);
    }

    protected static function publishFolder(string $from, string $to): void
    {
        $target = static::$targetDisk ?: static::$currentPathDisk;

        if (static::$altAppDisk && static::$altAppDisk->directoryExists($from)) {
            static::$filesystem->copyDirectory(
                static::$altAppDisk->path($from),
                $target->path($to)
            );
        } else {
            static::$filesystem->copyDirectory(
                static::$appDisk->path($from),
                $target->path($to)
            );
        }
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param string $title
     * @param string $separator
     * @return string
     */
    protected static function slug(string $title, string $separator = '-'): string
    {
        $language = data_get(static::$luraConfig, 'slug-language', 'en');
        $language = in_array($language, ['en', 'de', 'bg']) ? $language : 'en';

        return Str::slug($title, $separator, $language);
    }
}
