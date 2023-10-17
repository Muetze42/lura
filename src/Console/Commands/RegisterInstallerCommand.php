<?php

namespace NormanHuth\ConsoleApp\Console\Commands;

use Illuminate\Support\Str;
use NormanHuth\ConsoleApp\LuraCommand;

class RegisterInstallerCommand extends LuraCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register {repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register a Installer repository.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $repository = $this->argument('repository');

        $composerJson = $this->composerHome . '/composer.json';

        if (!file_exists($composerJson)) {
            $this->error('Composer home composer.json not found.');

            return;
        }

        $content = json_decode(file_get_contents($composerJson), true);

        $repositories = array_filter(array_keys(array_merge(
            data_get($content, 'require', []),
            data_get($content, 'require-dev', []),
        )), function ($repository) {
            return $repository != 'norman-huth/lura';
        });

        if (!in_array($repository, $repositories)) {
            $this->error('The package ' . $repository . ' is not installed.');

            return;
        }

        if (!is_dir($this->composerHome . '/vendor/' . $repository)) {
            $this->error('The package ' . $repository . ' directory is missing.');

            return;
        }

        $configRepositories = data_get($this->config, 'repositories', []);
        $configRepositories[] = $repository;
        $configRepositories = array_unique($configRepositories);
        data_set($this->config, 'repositories', $configRepositories);

        $repositoryConfigFile = $this->composerHome . '/vendor/' . $repository . '/config/lura-config.json';
        if (file_exists($repositoryConfigFile)) {
            $content = file_get_contents($repositoryConfigFile);
            if (Str::isJson($content)) {
                $repositoryConfig = json_decode($content, true);
            }
        }

        if (!empty($repositoryConfig)) {
            $userItems = data_get($this->config, 'repositories-config.' . $repository, []);
            foreach ($userItems as $key => $value) {
                $repositoryConfig[$key] = $value;
            }

            data_set($this->config, 'repositories-config.' . $repository, $repositoryConfig);
        }

        $this->setUserConfig($this->config);

        $this->info('Installer added!');
    }
}
