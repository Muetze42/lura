<?php

namespace Lura\Console;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ConfigCommand extends Command
{
    protected array $arrayCasts = [
        'ignore-lura-scripts'
    ];

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('config')
            ->addArgument('action', InputArgument::OPTIONAL)
            ->addArgument('param', InputArgument::OPTIONAL)
            ->addArgument('value', InputArgument::OPTIONAL)
        ;
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defaultConfig = require rootDir('config/lura-default.php');
        $action = $input->getArgument('action');
        $param = $input->getArgument('param');
        $value = $input->getArgument('value');

        if ($action) {
            switch ($action) {
                case 'set':
                    if (!$param || !$value) {
                        $output->writeln('<error>Missing param</error>');
                        $output->writeln('Example: <info>lura config set custom-app-path path/to/folder</info>');
                        return 2;
                    }

                    switch ($value) {
                        case 'null':
                            $value = null;
                            break;
                        case 'true':
                            $value = true;
                            break;
                        case 'false':
                            $value = false;
                            break;
                        case in_array($param, $this->arrayCasts):
                            $value = array_map('trim', explode(',', $value));
                    }

                    data_set(static::$luraConfig, $param, $value);

                    $command = static::composer('config --global home');
                    $process = Process::fromShellCommandline($command);
                    $process->run(function ($type, $line) {
                        if ($type == 'out') {
                            $line = trim($line);
                            $configFile = trim($line, '/\\').'/lura.json';

                            file_put_contents($configFile, json_encode(static::$luraConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                        }
                    });

                    $output->writeln('New config:');
                    $this->formatConfig(static::$luraConfig, $output);
                    $this->customPathNotExist($output);

                    break;
                case 'get':
                    $this->customPathNotExist($output);
                    if (!$param) {
                        $output->writeln('<error>Missing param</error>');
                    }
                    $this->formatConfig(data_get(static::$luraConfig, $param), $output);
                    return 0;
                default:
                    $output->writeln('<error>Action `'.$action.'` not exist</error>');
            }
            return 2;
        }

        $config = array_merge($defaultConfig, static::$luraConfig);
        $this->formatConfig($config, $output);

        return 0;
    }

    protected function formatConfig($config, $output, string $signs = '')
    {
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                if (is_array($value)) {
                    $output->writeln($signs.'[<comment>'.$key.'</comment>]: ');
                    $this->formatConfig($value, $output, $signs.'    ');
                } else {
                    $output->writeln($signs.'[<comment>'.$key.'</comment>]: <info>'.$this->formatValue($value).'</info>');
                }
            }
        } else {
            $output->writeln($signs.'<info>'.$config.'</info>');
        }
    }

    protected function formatValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    }
}
