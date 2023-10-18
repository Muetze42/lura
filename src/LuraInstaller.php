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
            if (
                $contents = file_get_contents(
                    'https://raw.githubusercontent.com/Muetze42/data/main/storage/versions.json'
                )
            ) {
                if (Str::isJson($contents)) {
                    static::$dependenciesVersions = json_decode($contents, true);
                }
            }
        }
        if (is_null(static::$dependenciesVersions)) {
            static::$dependenciesVersions = [];
        }

        return static::$dependenciesVersions;
    }
}
