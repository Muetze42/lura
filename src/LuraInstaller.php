<?php

namespace NormanHuth\Lura;

use NormanHuth\Helpers\Str;

abstract class LuraInstaller
{
    /**
     * Execute the installer console command.
     *
     * @param mixed|\NormanHuth\Lura\LuraCommand $command
     */
    abstract public function runLura(mixed $command);

    /**
     * Collected dependencies version from https://github.com/Muetze42/data.
     *
     * @var array{composer: array<array-key, string>, npm: array<array-key, string>}|null
     */
    protected static ?array $dependenciesVersions = null;

    /**
     * @var string
     */
    protected static string $versionsUrl = 'https://raw.githubusercontent.com/Muetze42/data/main/storage/versions.json';

    /**
     * Add a dependencies to json.
     *
     * @param array  $dependencies
     * @param string $package
     * @param string $version
     * @param bool   $updateVersion
     *
     * @return void
     */
    protected static function addDependency(
        array &$dependencies,
        string $package,
        string $version,
        bool $updateVersion = true
    ): void {
        if ($updateVersion) {
            $versions = static::getDependenciesVersions();

            $version = data_get(
                $versions,
                'composer.' . $package,
                data_get(
                    $versions,
                    'npm.' . $package,
                    $version
                )
            );
        }

        $dependencies = array_merge($dependencies, [$package => $version]);
        ksort($dependencies, SORT_NATURAL | SORT_FLAG_CASE);
    }

    /**
     * @return array
     */
    protected static function getDependenciesVersions(): array
    {
        if (is_null(static::$dependenciesVersions)) {
            try {
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => static::$versionsUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 6,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                if ($response && is_string($response) && Str::isJson($response)) {
                    static::$dependenciesVersions = json_decode($response, true);
                }
            } catch (\Exception) {
                // silent
            }
        }
        if (is_null(static::$dependenciesVersions)) {
            static::$dependenciesVersions = [];
        }

        return static::$dependenciesVersions;
    }
}
