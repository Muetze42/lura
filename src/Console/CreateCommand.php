<?php

namespace Lura\Console;


use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class CreateCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('create')
            ->setDescription('Create a new Laravel application');
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
        //$type = $this->getName();
        $type = 'create';
        $options = static::getOptions($type);

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'What do you want to create?',
            $options
        );
        $question->setErrorMessage('%s is invalid.');

        $option = $helper->ask($input, $output, $question);
        $output->writeln('<info>'.$option.'</info>');
        $optionStudly = Str::studly($option);

        $path = $type.'/'.$option;
        $file = $path.'/'.$optionStudly.'.php';

        if (static::$altAppDisk && static::$altAppDisk->exists($file)) {
            require static::$altAppDisk->path($file);
        } else {
            require static::$appDisk->path($file);
        }

        $class = $type.'\\'.$optionStudly;

        return (new $class)->runLura($input, $output, static::$appDisk, static::$altAppDisk, $path, $type, static::$luraConfig);
    }
}
