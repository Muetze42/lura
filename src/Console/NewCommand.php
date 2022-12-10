<?php

namespace Lura\Console;

class NewCommand extends CreateCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('new')
            ->setDescription('Alias for create command');
    }
}
