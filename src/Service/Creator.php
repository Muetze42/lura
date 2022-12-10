<?php

namespace Lura\Service;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Lura\Traits\CommandHelpers;
use Lura\Traits\ComposerHelper;
use Lura\Traits\FilesystemTrait;
use Lura\Traits\StingTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Creator extends Support
{
    use StingTrait, FilesystemTrait, CommandHelpers, ComposerHelper;

    public function runLura(InputInterface $input, OutputInterface $output, Filesystem $appDisk, null|Filesystem $altAppDisk, string $path, string $type, array $luraConfig): int
    {
        static::setFilesystem();
        static::$filesystem = new \Illuminate\Filesystem\Filesystem;
        static::setCommand($type);
        static::$input = $input;
        static::$output = $output;
        static::$composer = static::getComposer();
        static::setNewDisks($appDisk, $altAppDisk, $path);
        static::$luraConfig = $luraConfig;

        if (method_exists($this, 'executeLura')) {
            return $this->executeLura();
        } else {
            return self::INVALID;
        }
    }

    protected static function existCheck(string $path, bool $allowAllHiddenDirectories = true, bool $allowAllHiddenFiles = true, array $allowedDirectory = [], array $allowedFiles = []): bool
    {
        if (self::$currentPathDisk->directoryExists($path)) {
            $directories = self::$currentPathDisk->directories($path);
            foreach ($directories as $directory) {
                if ($allowAllHiddenDirectories && str_starts_with(basename($directory), '.')) {
                    continue;
                }
                if (!in_array(basename($directory), $allowedDirectory)) {
                    static::existCheckErrorMessage($allowAllHiddenDirectories, $allowAllHiddenFiles, $allowedDirectory, $allowedFiles);
                    return false;
                }
            }
            $files = self::$currentPathDisk->files($path);
            foreach ($files as $file) {
                if ($allowAllHiddenFiles && str_starts_with(basename($file), '.')) {
                    continue;
                }
                if (!in_array(basename($file), $allowedFiles)) {
                    static::existCheckErrorMessage($allowAllHiddenDirectories, $allowAllHiddenFiles, $allowedDirectory, $allowedFiles);
                    return false;
                }
            }

            if (!count($directories) && !count($files)) {
                self::$currentPathDisk->deleteDirectory($path);
            } else {
                static::$tempPath = self::getTempPath();
                static::$filesystem->moveDirectory(self::$currentPathDisk->path($path), self::$currentPathDisk->path(static::$tempPath));
            }
        }

        return true;
    }

    protected static function moveExistBack(string $path): void
    {
        if (static::$tempPath) {
            $directories = self::$currentPathDisk->directories(static::$tempPath);
            foreach ($directories as $directory) {
                static::$filesystem->moveDirectory(
                    self::$currentPathDisk->path($directory),
                    self::$currentPathDisk->path($path.'/'.basename($directory)),
                    true
                );
            }
            $files = self::$currentPathDisk->files(static::$tempPath);
            foreach ($files as $file) {
                static::$filesystem->move(
                    self::$currentPathDisk->path($file),
                    self::$currentPathDisk->path($path.'/'.basename($file))
                );
            }
            self::$currentPathDisk->deleteDirectory(static::$tempPath);
        }
    }

    protected static function getTempPath(string $directoryPrefix = 'temp-'): string
    {
        $tempDirectory = $directoryPrefix.Str::random(6);
        if (self::$currentPathDisk->directoryExists($tempDirectory)) {
            return self::getTempPath($directoryPrefix);
        }

        return $tempDirectory;
    }

    protected static function existCheckErrorMessage(bool $allowAllHiddenDirectories = true, bool $allowAllHiddenFiles = false, array $allowedDirectory = [], array $allowedFiles = []): void
    {
        static::$output->writeln('<error>'.self::$currentPathDisk->path(static::$appFolder).' already exist and is not empty.</error>');
        if ($allowAllHiddenDirectories) {
            static::$output->writeln('<info>Allowed are all hidden directories.</info> Example: /.git');
        }
        if (count($allowedDirectory)) {
            static::$output->writeln('<info>Allowed directories: '.implode(', ', $allowedDirectory).'</info>');
        }
        if ($allowAllHiddenFiles) {
            static::$output->writeln('<info>Allowed are all hidden files.</info> Example: .config');
        }
        if (count($allowedFiles)) {
            static::$output->writeln('<info>Allowed files: '.implode(', ', $allowedFiles).'</info>');
        }
    }

    protected static function addPackage(array $key, string $package, string $version): array
    {
        $key = array_merge($key, [$package => $version]);
        ksort($key, SORT_NATURAL | SORT_FLAG_CASE);

        return $key;
    }

    abstract protected function executeLura(): int;
}
