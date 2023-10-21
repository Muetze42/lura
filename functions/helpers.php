<?php

if (!function_exists('strReplace')) {
    /**
     * Replace all occurrences of the search string with the replacement string.
     *
     * @param array|string $search
     * @param array|string $replace
     * @param array|string $subject
     *
     * @return array|string
     */
    function strReplace(array|string $search, array|string $replace, array|string $subject): array|string
    {
        $subject = str_replace("\t", '    ', $subject);

        return str_replace($search, $replace, $subject);
    }
}

if (!function_exists('strReplace')) {
    /**
     * Case-insensitive version of strReplace.
     *
     * @param array|string $search
     * @param array|string $replace
     * @param array|string $subject
     *
     * @return array|string
     */
    function strIReplace(array|string $search, array|string $replace, array|string $subject): array|string
    {
        $subject = str_replace("\t", '    ', $subject);

        return str_ireplace($search, $replace, $subject);
    }
}
