<?php

namespace NormanHuth\ConsoleApp;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class LuraCommand extends Command
{
    /**
     * The Installer Configurations
     *
     * @var array
     */
    public array $installerConfig;

    public FilesystemManager $filesystemManager;
    public Filesystem $cwdDisk;
    public string $composer;
    public string $composerHome;
    public bool $initialized = false;
    public CacheRepository $cache;
    public array $config;
    public string $userConfigFile;
    public string $tempPath = '';
    public \Illuminate\Filesystem\Filesystem $filesystem;

    /**
     * Execute the console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidArgumentException
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setFilesystemManager();
        $this->setCwdDisk();
        $this->composer = $this->findComposer();
        $this->setUpCache();
        $this->setComposerHome();
        $this->setConfig();
        $this->filesystem = new \Illuminate\Filesystem\Filesystem;

        return parent::execute($input, $output);
    }

    /**
     * @return void
     */
    protected function setConfig()
    {
        $this->config = json_decode(file_get_contents(__DIR__.'/../config/lura-config.json'), true);

        $this->userConfigFile = $this->composerHome.'/lura-config.json';
        $userConfig = file_exists($this->userConfigFile) ? json_decode(file_get_contents($this->userConfigFile), true) : [];

        $repositories = data_get($userConfig, 'repositories', []);
        $repositoriesConfig = data_get($userConfig, 'repositories-config', []);

        foreach ($repositories as $repository) {
            $repositoryConfigFile = $this->composerHome.'/vendor/'.$repository.'/config/lura-config.json';
            if (file_exists($repositoryConfigFile)) {
                $content = file_get_contents($repositoryConfigFile);
                if (isJson($content)) {
                    $repositoriesConfig[$repository] = json_decode($content, true);
                    $userItems = data_get($userConfig, 'repositories-config.'.$repository, []);
                    foreach ($userItems as $key => $value) {
                        $repositoriesConfig[$repository][$key] = $value;
                    }
                }
            }
        }

        foreach ($userConfig as $key => $value) {
            if (!in_array($key, ['repositories-config', 'repositories'])) {
                data_set($this->config, $key, $value);
            }
        }

        data_set($this->config, 'repositories-config', $repositoriesConfig);
        data_set($this->config, 'repositories', $repositories);

        $this->setUserConfig($this->config);
    }

    /**
     * @param array $config
     * @return void
     */
    protected function setUserConfig(array $config)
    {
        file_put_contents($this->userConfigFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return void
     */
    protected function setUpCache()
    {
        $app = new Container;
        $app->singleton('files', function () {
            return new \Illuminate\Filesystem\Filesystem;
        });
        $app->singleton('config', function () {
            return [
                'cache.default'      => 'files',
                'cache.stores.files' => [
                    'driver' => 'file',
                    'path'   => __DIR__.'/../cache',
                ]
            ];
        });

        /* @var Application $app */
        $cacheManager = new CacheManager($app);

        $this->cache = $cacheManager->driver();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function setComposerHome(string $variable = 'COMPOSER_HOME')
    {
        $cached = $this->cache->get($variable);

        if ($cached && is_dir($cached)) {
            $this->composerHome = $cached;

            return;
        }

        $env = getenv($variable);

        if ($env) {
            $this->composerHome = $env;
        } else {
            $command = $this->composer.' config --global home';

            $process = Process::fromShellCommandline($command);
            $process->run(function ($type, $line) use ($process) {
                if ($type == 'out') {
                    $line = trim($line);
                    if (!empty($line)) {
                        $this->composerHome = $line;
                    }
                }
            });
        }

        $this->cache->forever($variable, $this->composerHome);
    }

    /**
     * @return void
     */
    protected function setCwdDisk()
    {
        $this->cwdDisk = $this->createFilesystem(getcwd());
    }

    /**
     * @param string $path
     * @return Filesystem
     */
    public function createFilesystem(string $path): Filesystem
    {
        return $this->filesystemManager->build([
            'driver' => 'local',
            'root'   => trim($path, '/\\'),
        ]);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getRepoSlug(string $name): string
    {
        return trim(preg_replace('/[^A-Za-z0-9-_.]+/', '-', $name));
    }

    /**
     * @return void
     */
    protected function setFilesystemManager(): void
    {
        $container = new Container;
        $container->instance('app', $container);
        $container['config'] = new Repository(require __DIR__.'/../config/filesystems.php');

        /** @var Application $container */
        $this->filesystemManager = new FilesystemManager($container);
    }

    /**
     * Get the composer command for the environment.
     */
    public function findComposer(): string
    {
        $composerPath = getcwd().'/composer.phar';

        return file_exists($composerPath) ? '"'.PHP_BINARY.'" '.$composerPath : 'composer';
    }

    /**
     * @param string $path
     * @param bool $allowAllHiddenDirectories
     * @param bool $allowAllHiddenFiles
     * @param array $allowedDirectory
     * @param array $allowedFiles
     * @return bool
     */
    public function existCheck(string $path, bool $allowAllHiddenDirectories = true, bool $allowAllHiddenFiles = true, array $allowedDirectory = [], array $allowedFiles = []): bool
    {
        if ($this->cwdDisk->directoryExists($path)) {
            $directories = $this->cwdDisk->directories($path);
            foreach ($directories as $directory) {
                if ($allowAllHiddenDirectories && str_starts_with(basename($directory), '.')) {
                    continue;
                }
                if (!in_array(basename($directory), $allowedDirectory)) {
                    return false;
                }
            }
            $files = $this->cwdDisk->files($path);
            foreach ($files as $file) {
                if ($allowAllHiddenFiles && str_starts_with(basename($file), '.')) {
                    continue;
                }
                if (!in_array(basename($file), $allowedFiles)) {
                    return false;
                }
            }

            if (!count($directories) && !count($files)) {
                $this->cwdDisk->deleteDirectory($path);
            } else {
                $this->tempPath = $this->getTempPath();
                $this->filesystem->moveDirectory($this->cwdDisk->path($path), $this->cwdDisk->path($this->tempPath));
            }
        }

        return true;
    }

    /**
     * @param string $path
     * @return void
     */
    public function moveExistBack(string $path): void
    {
        if ($this->tempPath) {
            $directories = $this->cwdDisk->directories($this->tempPath);
            foreach ($directories as $directory) {
                $this->filesystem->moveDirectory(
                    $this->cwdDisk->path($directory),
                    $this->cwdDisk->path($path.'/'.basename($directory)),
                    true
                );
            }
            $files = $this->cwdDisk->files($this->tempPath);
            foreach ($files as $file) {
                $this->filesystem->move(
                    $this->cwdDisk->path($file),
                    $this->cwdDisk->path($path.'/'.basename($file))
                );
            }

            $this->cwdDisk->deleteDirectory($this->tempPath);
        }
    }

    /**
     * @param string $directoryPrefix
     * @return string
     */
    public function getTempPath(string $directoryPrefix = 'temp-'): string
    {
        $tempDirectory = $directoryPrefix.Str::random(6);
        if ($this->cwdDisk->directoryExists($tempDirectory)) {
            return $this->getTempPath($directoryPrefix);
        }

        return $tempDirectory;
    }

    /**
     * @param string $pattern
     * @param string $replace
     * @param string $subject
     * @param int $occurrence
     * @return string
     */
    public function replaceNth(string $pattern, string $replace, string $subject, int $occurrence = 1): string
    {
        return preg_replace_callback($pattern, function ($m) use (&$counter, $replace, $occurrence) {
            if ($counter++ == $occurrence) {
                return $replace;
            }

            return $m[0];

        }, $subject);
    }
}
