<?php

if (!function_exists('rootDir')) {
    /**
     * @param string $path
     * @return string
     */
    function rootDir(string $path = ''): string
    {
        $path = trim($path, '/\\');

        return realpath(__DIR__.'/../'.$path);
    }
}

if (!function_exists('replaceNth')) {
    /**
     * @param string $pattern
     * @param string $replace
     * @param string $subject
     * @param int $occurrence
     * @return string
     */
    function replaceNth(string $pattern, string $replace, string $subject, int $occurrence = 1): string
    {
        return preg_replace_callback($pattern, function ($m) use (&$counter, $replace, $occurrence) {
            if ($counter++ == $occurrence) {
                return $replace;
            }

            return $m[0];

        }, $subject);
    }
}
