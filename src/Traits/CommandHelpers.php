<?php

namespace Lura\Traits;


use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

trait CommandHelpers
{
    protected static function askingForInformation(string $text, bool $required = true, string|bool|int|float|null $default = ''): string|null
    {
        $helper = static::$command->getHelper('question');
        if (is_null($default)) {
            $default = '';
        }
        $question = new Question("\n".$text."\n", $default);

        $answer = $helper->ask(static::$input, static::$output, $question);

        if ($required && !$answer) {
            return self::askingForInformation($text, $required);
        }

        return $answer;
    }

    protected static function askingForConfirmation(string $text, bool $default = true, string $trueAnswerRegex = '/^y/i'): bool
    {
        $helper = static::$command->getHelper('question');
        $question = new ConfirmationQuestion("\n".$text."\n", $default, $trueAnswerRegex);

        if ($helper->ask(static::$input, static::$output, $question)) {
            return true;
        }

        return false;
    }

    protected static function chooseFromList(string $text, array $choices, mixed $default = null, string $errorMessage = '%s is invalid.')
    {
        $helper = static::$command->getHelper('question');
        $question = new ChoiceQuestion(
            "\n".$text,
            $choices,
            $default
        );
        $question->setErrorMessage($errorMessage);

        return $helper->ask(static::$input, static::$output, $question);
    }
}
