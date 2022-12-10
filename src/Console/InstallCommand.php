<?php

namespace Lura\Console;


use Lura\Service\Installer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class InstallCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('install')
            ->setDescription('Install a new package');
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
        $output->writeln('<error>You are using a deprecated Lura Version. Update: https://github.com/Muetze42/lura</error>');

        $this->customPathNotExist($output);
        $getOptions = static::getOptions('install');

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Which packages do you want to install??',
            $getOptions
        );
        $question->setErrorMessage('%s is invalid.');
        $question->setMultiselect(true);
        $options = $helper->ask($input, $output, $question);

        return (new Installer)->runLura($input, $output, static::$appDisk, static::$altAppDisk, $options, static::$luraConfig);
    }
}
