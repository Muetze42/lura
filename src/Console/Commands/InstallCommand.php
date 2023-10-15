<?php

namespace NormanHuth\ConsoleApp\Console\Commands;

use NormanHuth\ConsoleApp\LuraCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class InstallCommand extends LuraCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install';

    /**
     * All available registered Installers
     *
     * @var array
     */
    protected array $installers = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new app';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $repositories = data_get($this->config, 'repositories', []);

        foreach ($repositories as $repository) {
            $classFile = $this->composerHome.'/vendor/'.$repository.'/Lura/Installer.php';
            $composerFile = $this->composerHome.'/vendor/'.$repository.'/composer.json';
            if (file_exists($classFile)) {
                $content = file_get_contents($composerFile);
                $data = json_decode($content, true);
                $this->installers[] = data_get($data, 'name');
            }
        }

        if (empty($this->installers)) {
            $this->error('No Installer found. Please install and register 1 installer or more');
            return SymfonyCommand::FAILURE;
        }

        $installer = count($this->installers) == 1 ? 0 : $this->choice('Which installer do you want to use?', $this->installers);

        if (!$installer) {
            $installer = $this->installers[0];
        }

        $repositoryConfigFile = $this->composerHome.'/vendor/'.$installer.'/config/lura-config.json';

        $this->installerConfig = data_get($this->config, 'repositories-config.'.$installer, []);
        $this->alert('Starting '.$installer);

        require_once $this->composerHome.'/vendor/'.$installer.'/Lura/Installer.php';

        return (new \Installer)->runLura($this);
    }
}
