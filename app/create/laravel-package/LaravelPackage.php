<?php

namespace create;

use Illuminate\Support\Str;
use Lura\Service\Creator;
use function Symfony\Component\Translation\t;

class LaravelPackage extends Creator
{
    protected string $vendorName;
    protected string $vendor;
    protected string $packageName;
    protected string $description;
    protected string $authorName;
    protected string $authorEmail;
    protected string $authorHomepage;
    protected string $authorRole;
    protected string $licence;
    protected string $namespace;

    /**
     * @throws \Exception
     */
    protected function executeLura(): int
    {
        $this->packageName = static::askingForInformation('Package name');
        $this->packageName = static::slug($this->packageName);
        if (!static::existCheck($this->packageName)) {
            return self::FAILURE;
        }
        static::setTargetDisk($this->packageName);

        $git = static::getGitConfig();
        if (!empty($_SERVER['COMPOSER_DEFAULT_VENDOR'])) {
            $defaultVendor = $_SERVER['COMPOSER_DEFAULT_VENDOR'];
        } elseif (isset($git['github.user'])) {
            $defaultVendor = $git['github.user'];
        } elseif (!empty($_SERVER['USERNAME'])) {
            $defaultVendor = $_SERVER['USERNAME'];
        } elseif (!empty($_SERVER['USER'])) {
            $defaultVendor = $_SERVER['USER'];
        } elseif (get_current_user()) {
            $defaultVendor = get_current_user();
        } else {
            $defaultVendor = null;
        }

        if ($defaultVendor) {
            $defaultVendor = static::slug($defaultVendor);
        }

        $this->vendorName = static::askingForInformation('Vendor [<comment>'.(string)$defaultVendor.'</comment>]', true, $defaultVendor);

        $this->vendorName = static::slug($this->vendorName);

        $this->vendor = $this->vendorName.'/'.$this->packageName;
        static::$output->write('Vendor: <info>'.$this->vendor.'</info>');

        $defaultNameSpace = Str::studly($this->vendorName).'\\'.Str::studly($this->packageName);
        $this->namespace = static::askingForInformation('Namespace [<comment>'.$defaultNameSpace.'</comment>]', true, $defaultNameSpace);

        $this->description = static::askingForInformation('Description', false);

        $defaultLicence = data_get(static::$luraConfig, 'default-licence');
        if (!$defaultLicence && !empty($_SERVER['COMPOSER_DEFAULT_LICENSE'])) {
            $defaultLicence = $_SERVER['COMPOSER_DEFAULT_LICENSE'];
        }

        $this->licence = static::askingForInformation('Licence [<comment>'.$defaultLicence.'</comment>]', true, $defaultLicence);
        static::$output->write('Licence: <info>'.$this->licence.'</info>');

        $defaultAuthorName = data_get(static::$luraConfig, 'default-author.name');
        if (!$defaultAuthorName) {
            if (!empty($_SERVER['COMPOSER_DEFAULT_AUTHOR'])) {
                $defaultAuthorName = $_SERVER['COMPOSER_DEFAULT_AUTHOR'];
            } elseif (isset($git['user.name'])) {
                $defaultAuthorName = $git['user.name'];
            } else {
                $defaultAuthorName = null;
            }
        }

        $this->authorName = static::askingForInformation('Author Name [<comment>'.$defaultAuthorName.'</comment>]', false, $defaultAuthorName);

        if ($this->authorName) {
            $defaultAuthorEmail = data_get(static::$luraConfig, 'default-author.email');
            if (!$defaultAuthorEmail) {
                if (!empty($_SERVER['COMPOSER_DEFAULT_EMAIL'])) {
                    $defaultAuthorEmail = $_SERVER['COMPOSER_DEFAULT_EMAIL'];
                } elseif (isset($git['user.email'])) {
                    $defaultAuthorEmail = $git['user.email'];
                }
            }

            $this->authorEmail = static::askingForInformation('Author Email [<comment>'.$defaultAuthorEmail.'</comment>]', false, $defaultAuthorEmail);

            $defaultAuthorHomepage = data_get(static::$luraConfig, 'default-author.homepage');
            if (!$defaultAuthorHomepage) {
                if (!empty($_SERVER['COMPOSER_DEFAULT_HOMEPAGE'])) {
                    $defaultAuthorHomepage = $_SERVER['COMPOSER_DEFAULT_HOMEPAGE'];
                } elseif (isset($git['user.homepage'])) {
                    $defaultAuthorHomepage = $git['user.homepage'];
                } elseif (isset($git['user.website'])) {
                    $defaultAuthorHomepage = $git['user.website'];
                } elseif (isset($git['user.blog'])) {
                    $defaultAuthorHomepage = $git['user.blog'];
                }
            }

            $this->authorHomepage = static::askingForInformation('Author Homepage [<comment>'.$defaultAuthorHomepage.'</comment>]', false, $defaultAuthorHomepage);

            $defaultRole = data_get(static::$luraConfig, 'default-author.role') ? data_get(static::$luraConfig, 'default-author.role') : null;
            $this->authorRole = static::askingForInformation('Author Role [<comment>'.$defaultRole.'</comment>]', false, $defaultRole);
        }

        $this->createPackage();

        static::moveExistBack($this->packageName);

        return static::SUCCESS;
    }

    protected function createPackage()
    {
        static::publishFolder('package', '');
        $composerJson = json_decode(static::$targetDisk->get('composer.json'), true);
        data_set($composerJson, 'name', $this->vendor);

        if ($this->description) {
            data_set($composerJson, 'description', $this->description);
        } else {
            unset($composerJson['description']);
        }

        $author = null;
        if ($this->authorName) {
            $author['name'] = $this->authorName;

            if ($this->authorEmail) {
                $author['email'] = $this->authorEmail;
            }
            if ($this->authorHomepage) {
                $author['homepage'] = $this->authorHomepage;
            }
            if ($this->authorRole) {
                $author['role'] = $this->authorRole;
            }
        }
        if ($author) {
            data_set($composerJson, 'authors', [$author]);
        } else {
            unset($composerJson['authors']);
        }

        data_set($composerJson, 'autoload.psr-4', [$this->namespace.'\\' => 'src/']);
        data_set($composerJson, 'extra.laravel.providers', [$this->namespace.'\\ServiceProvider']);

        static::$targetDisk->put('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        static::$targetDisk->move('gitignore.txt', '.gitignore');

        $file = 'src/ServiceProvider.php';
        static::$targetDisk->put(
            $file,
            str_replace('Lura', $this->namespace, static::$targetDisk->get($file))
        );
    }
}
