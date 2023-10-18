<?php

namespace NormanHuth\Lura\Console\Commands;

use NormanHuth\Lura\LuraCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Helper\Helper;

class ListCommand extends LuraCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list';

    /**
     * Names of the commands that should not be listed.
     *
     * @var array
     */
    protected array $exceptCommands = [
        'completion',
        'help',
        'install',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ListCommand displays the list of all available commands for the application.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $description = new ApplicationDescription($this->getApplication());
        $commands = $description->getCommands();
        $namespaces = $description->getNamespaces();

        $this->info('„' . $this->getApplication()->getName() . '“ Commands');
        $this->comment('Available commands:');

        $width = $this->getColumnWidth(
            array_merge(
                ...array_values(
                    array_map(
                        fn($namespace) => array_intersect(
                            $namespace['commands'],
                            array_keys($commands)
                        ),
                        array_values($namespaces)
                    )
                )
            )
        );

        foreach ($namespaces as $namespace) {
            $namespace['commands'] = array_filter($namespace['commands'], fn($name) => isset($commands[$name]));

            if (!$namespace['commands']) {
                continue;
            }
            foreach ($namespace['commands'] as $name) {
                $spacingWidth = $width - Helper::width($name);
                $command = $commands[$name];
                if (in_array($name, $this->exceptCommands)) {
                    continue;
                }
                $commandAliases = $name === $command->getName() ? $this->getCommandAliasesText($command) : '';

                $this->line(sprintf(
                    '  <info>%s</info>%s%s',
                    $name,
                    str_repeat(' ', $spacingWidth),
                    $commandAliases . $command->getDescription()
                ));
            }
        }
    }

    /**
     * @param array<Command|string> $commands
     */
    protected function getColumnWidth(array $commands): int
    {
        $widths = [];
        foreach ($commands as $command) {
            if ($command instanceof Command) {
                $widths[] = Helper::width($command->getName());
                foreach ($command->getAliases() as $alias) {
                    $widths[] = Helper::width($alias);
                }
            } else {
                $widths[] = Helper::width($command);
            }
        }

        return $widths ? max($widths) + 2 : 0;
    }

    /**
     * Formats command aliases to show them in the command description.
     */
    protected function getCommandAliasesText(Command $command): string
    {
        $text = '';
        $aliases = $command->getAliases();

        if ($aliases) {
            $text = '[' . implode('|', $aliases) . '] ';
        }

        return $text;
    }
}
