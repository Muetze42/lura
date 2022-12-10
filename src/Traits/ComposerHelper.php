<?php

namespace Lura\Traits;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

trait ComposerHelper
{
    /**
     * From https://github.com/composer/pcre
     *
     * @throws \Exception
     */
    public static function matchAll(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int
    {
        if (($flags & PREG_OFFSET_CAPTURE) !== 0) {
            throw new \InvalidArgumentException('PREG_OFFSET_CAPTURE is not supported as it changes the type of $matches, use matchAllWithOffsets() instead');
        }

        if (($flags & PREG_SET_ORDER) !== 0) {
            throw new \InvalidArgumentException('PREG_SET_ORDER is not supported as it changes the type of $matches');
        }

        $result = preg_match_all($pattern, $subject, $matches, $flags | PREG_UNMATCHED_AS_NULL, $offset);
        if ($result === false || /* PHP < 8 may return null */ $result === null) {
            $code = preg_last_error();

            if (is_array($pattern)) {
                $pattern = implode(', ', $pattern);
            }

            throw new \Exception('preg_match_all(): failed executing "'.$pattern.'":'.self::pcreLastErrorMessage($code), $code);
        }

        return $result;
    }

    /**
     * From https://github.com/composer/pcre
     *
     * @param $code
     * @return int|string
     */
    protected static function pcreLastErrorMessage($code): int|string
    {
        if (function_exists('preg_last_error_msg')) {
            return preg_last_error_msg();
        }

        // older php versions did not set the code properly in all cases
        if (PHP_VERSION_ID < 70201 && $code === 0) {
            return 'UNDEFINED_ERROR';
        }

        $constants = get_defined_constants(true);
        if (!isset($constants['pcre'])) {
            return 'UNDEFINED_ERROR';
        }

        foreach ($constants['pcre'] as $const => $val) {
            if ($val === $code && substr($const, -6) === '_ERROR') {
                return $const;
            }
        }

        return 'UNDEFINED_ERROR';
    }

    /**
     * From https://github.com/composer/composer
     *
     * @return array<string, string>
     * @throws \Exception
     */
    protected static function getGitConfig(): array
    {
        if (static::$gitConfig !== null) {
            return static::$gitConfig;
        }

        $finder = new ExecutableFinder();
        $gitBin = $finder->find('git');

        $cmd = new Process(array($gitBin, 'config', '-l'));
        $cmd->run();

        if ($cmd->isSuccessful()) {
            static::$gitConfig = [];
            static::matchAll('{^([^=]+)=(.*)$}m', $cmd->getOutput(), $matches);
            foreach ($matches[1] as $key => $match) {
                static::$gitConfig[$match] = $matches[2][$key];
            }

            return static::$gitConfig;
        }

        return static::$gitConfig = [];
    }
}
