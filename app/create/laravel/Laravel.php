<?php

namespace create;

use Lura\Service\Creator;
use Lura\Traits\LaravelHelpers;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Laravel extends Creator
{
    use LaravelHelpers;

    protected static string $appName;
    protected static string $starterKit;
    protected static bool $installNova = false;
    protected static bool $installInertia = false;
    protected static bool $jetstreamTeams = true;
    protected static bool $SSR = true;
    protected static bool $docker = false;
    protected static string $appFolder = '';
    protected static int $laravelMainVersion = 0;

    protected function executeLura(): int
    {
        static::$appName = static::askingForInformation('Please enter the app name');
        static::$appFolder = $this->formatPath(static::$appName);

        if (!static::existCheck(static::$appFolder)) {
            return self::FAILURE;
        }

        $this->questions();
        $this->install();
        $this->createEnv();
        $this->changeComposerJson();
        $this->changePackageJson();
        static::moveExistBack(static::$appFolder);
        $this->composerInstall();
        $this->afterComposerInstall();
        $this->updateAppServiceProvider();

        return self::SUCCESS;
    }

    protected function formatPath(string $path): string
    {
        return static::slug($path);
    }

    protected function updateAppServiceProvider()
    {
        $target = 'app/Providers/AppServiceProvider.php';
        $content = static::$targetDisk->get($target);

        $content = str_replace(
            'use Illuminate\Support\ServiceProvider;',
            "use Illuminate\Support\ServiceProvider;\n#use Illuminate\Http\Resources\Json\JsonResource;\nuse Illuminate\Validation\Rules\Password;",
            $content);
        $content = replaceNth('/\/\//', '#JsonResource::withoutWrapping();

        Password::defaults(static function () {
            return Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });', $content);

        static::$targetDisk->put($target, $content);
    }

    protected function afterComposerInstall()
    {
        /* Change stack logging channel driver to daily */
        $content = static::$targetDisk->get('config/logging.php');
        $content = str_replace("'channels' => ['single']", "'channels' => ['daily']", $content);
        static::$targetDisk->put('config/logging.php', $content);

        static::runCommand('php artisan key:generate --ansi');
        if (static::$installNova) {
            static::publishFolder('nova', '');
            static::runCommand('php artisan nova:install');
        }

        switch (static::$starterKit) {
            case 'Breeze':
                static::runCommand('php artisan breeze:install');
                break;
            case 'Breeze with Vue scaffolding':
                $ssr = static::$SSR ? ' --ssr' : '';
                static::runCommand('php artisan breeze:install vue'.$ssr);
                break;
            case 'Breeze with React scaffolding':
                $ssr = static::$SSR ? ' --ssr' : '';
                static::runCommand('php artisan breeze:install react'.$ssr);
                break;
            case 'Breeze with Next.js / API scaffolding':
                static::runCommand('php artisan breeze:install api');
                break;
            case 'Jetstream with Livewire':
                $teams = static::$jetstreamTeams ? ' --teams' : '';
                static::runCommand('php artisan jetstream:install livewire'.$teams);
                break;
            case 'Jetstream with Inertia':
                $ssr = static::$SSR ? ' --ssr' : '';
                $teams = static::$jetstreamTeams ? ' --teams' : '';
                static::runCommand('php artisan jetstream:install inertia'.$teams.$ssr);
                break;
        }

        if (static::$installInertia) {
            static::runCommand('php artisan inertia:middleware');

            $search = '\\Illuminate\\Routing\\Middleware\\SubstituteBindings::class,';
            $replace = '\\Illuminate\\Routing\\Middleware\\SubstituteBindings::class,'."\n".'            \\App\\Http\\Middleware\\HandleInertiaRequests::class,';
            $subject = static::$targetDisk->get('app/Http/Kernel.php');
            $content = preg_replace('/'.preg_quote($search, '/').'/',
                $replace,
                $subject,
                1
            );

            static::$targetDisk->put('app/Http/Kernel.php', $content);
        }
    }

    protected function composerInstall()
    {
        static::runCommand(static::composer('install --prefer-dist'));
    }

    protected function changePackageJson()
    {
        $packageJson = json_decode(static::$targetDisk->get('package.json'), true);
        $devDependencies = data_get($packageJson, 'devDependencies', []);
        $dependencies = data_get($packageJson, 'dependencies', []);

        if (static::$installInertia) {
            $devDependencies = static::addPackage($devDependencies, 'vue-loader', '16.x');
            $dependencies = static::addPackage($dependencies, '@babel/plugin-syntax-dynamic-import', '7.x');
            $dependencies = static::addPackage($dependencies, '@inertiajs/inertia', '0.x');
            $dependencies = static::addPackage($dependencies, '@inertiajs/inertia-vue3', '0.x');
            $dependencies = static::addPackage($dependencies, '@inertiajs/progress', '0.x');
            $dependencies = static::addPackage($dependencies, 'vue', '3.x');
        }


        data_set($packageJson, 'dependencies', $dependencies);
        data_set($packageJson, 'devDependencies', $devDependencies);
        static::$targetDisk->put('package.json', json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected function changeComposerJson()
    {
        $composerJson = json_decode(static::$targetDisk->get('composer.json'), true);
        $requirements = data_get($composerJson, 'require', []);
        $devRequirements = data_get($composerJson, 'require-dev', []);
        $php = $composerJson['require']['php'];
        unset($composerJson['require']['php']);

        $version = data_get($requirements, 'laravel/framework');
        $version = explode('.', $version)[0];
        static::$laravelMainVersion = preg_replace('/\D/', '', $version);

        if (static::$installNova) {
            data_set($composerJson, 'repositories', [
                [
                    'type' => 'composer',
                    'url'  => 'https://nova.laravel.com'
                ]
            ]);

            $requirements = static::addPackage($requirements, 'laravel/nova', '~4.0');
        }

        if (in_array(static::$starterKit, [
            'Breeze',
            'Breeze with Vue scaffolding',
            'Breeze with React scaffolding',
            'Breeze with Next.js / API scaffolding',
        ])) {
            $devRequirements = static::addPackage($devRequirements, 'laravel/breeze', '~1.0');
        }

        if (in_array(static::$starterKit, [
            'Jetstream with Livewire',
            'Jetstream with Inertia',
        ])) {
            $devRequirements = static::addPackage($devRequirements, 'laravel/jetstream', '~2.0');
        }

        if (static::$installInertia) {
            $requirements = static::addPackage($requirements, 'inertiajs/inertia-laravel', '~0.0');
        }

        data_set($composerJson, 'require', array_merge(['php' => $php], $requirements));
        data_set($composerJson, 'require-dev', $devRequirements);
        static::$targetDisk->put('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected function createEnv()
    {
        $content = '';
        $lines = explode("\n", trim(static::$targetDisk->get('.env.example')));
        foreach ($lines as $line) {
            $line = trim($line);
            $key = explode('=', $line)[0];

            switch ($key) {
                case 'APP_NAME':
                    $content .= 'APP_NAME="'.static::$appName.'"';
                    break;
                case 'APP_URL':
                    /** @noinspection HttpUrlsUsage */
                    $content .= 'APP_URL=http://'.static::$appFolder.'.test';
                    break;
                case 'DB_HOST':
                    $value = static::$docker ? 'mysql' : '127.0.0.1';
                    $content .= 'DB_HOST='.$value;
                    break;
                case 'DB_DATABASE':
                    $content .= 'DB_DATABASE='.static::slug(static::$appName, '_');
                    break;
                case 'DB_USERNAME':
                    $value = static::$docker ? 'sail' : 'root';
                    $content .= 'DB_USERNAME='.$value;
                    break;
                case 'DB_PASSWORD':
                    $value = static::$docker ? 'password' : '';
                    $content .= 'DB_PASSWORD='.$value;
                    break;
                case 'MEMCACHED_HOST':
                    $value = static::$docker ? 'memcached' : '127.0.0.1';
                    $content .= 'MEMCACHED_HOST='.$value;
                    break;
                case 'REDIS_HOST':
                    $value = static::$docker ? 'redis' : '127.0.0.1';
                    $content .= 'REDIS_HOST='.$value;
                    break;
                default:
                    $content .= $line;
            }
            $content .= "\n";
        }

        if (static::$docker) {
            $content .= "\n\nSCOUT_DRIVER=meilisearch\nMEILISEARCH_HOST=http://meilisearch:7700";
        }

        if (static::$installNova) {
            $content = str_replace('LOG_CHANNEL', "NOVA_LICENSE_KEY=\n\nLOG_CHANNEL", $content);
        }

        $content = trim($content);
        static::$targetDisk->put('.env.example', $content);
        if (static::$installNova) {
            $content = str_replace('NOVA_LICENSE_KEY=', 'NOVA_LICENSE_KEY='.$this->getNovaKey(), $content);
        }
        static::$targetDisk->put('.env', $content);
    }

    protected function questions()
    {
        static::$starterKit = static::chooseFromList('Install starter kit? [<comment>0</comment>]', [
            'no',
            'Breeze',
            'Breeze with Vue scaffolding',
            'Breeze with React scaffolding',
            'Breeze with Next.js / API scaffolding',
            'Jetstream with Livewire',
            'Jetstream with Inertia',
        ], 'no');

        static::$output->writeln('Starter Kit: <info>'.static::$starterKit.'</info>');

        if (in_array(static::$starterKit, ['Jetstream with Livewire', 'Jetstream with Inertia'])) {
            static::$jetstreamTeams = static::askingForConfirmation('Enable team support? [<comment>y</comment>]');
            $word = static::$jetstreamTeams ? 'with' : 'without';
            static::$output->writeln('Install Jetstream <info>'.$word.'</info> team support');
        }

        if (in_array(static::$starterKit, ['Jetstream with Inertia', 'Breeze with Vue scaffolding', 'Breeze with React scaffolding'])) {
            static::$SSR = static::askingForConfirmation('Install stack with SSR support? [<comment>n</comment>]', false);
            $word = static::$SSR ? 'with' : 'without';
            static::$output->writeln('Install Jetstream <info>'.$word.'</info> SSR support');
        }

        if (data_get(static::$luraConfig, 'laravel.create.nova', true)) {
            static::$installNova = static::askingForConfirmation('Install Laravel Nova? [<comment>n</comment>]', false);
            $word = static::$installNova ? 'Install' : 'Don’t install';
            static::$output->writeln('<info>'.$word.' Laravel Nova</info>');
        }

        if (static::$starterKit == 'no' && data_get(static::$luraConfig, 'laravel.create.inertia', true)) {
            static::$installInertia = static::askingForConfirmation('Install Inertia? [<comment>n</comment>]', false);
            $word = static::$installInertia ? 'Install' : 'Don’t install';
            static::$output->writeln('<info>'.$word.' Inertia</info>');
        }

        if (data_get(static::$luraConfig, 'laravel.create.docker', true)) {
            static::$docker = static::askingForConfirmation('Add Docker files? [<comment>n</comment>]', false);
            $word = static::$docker ? 'Add' : 'Don’t add';
            static::$output->writeln('<info>'.$word.' Docker files</info>');
        }
    }

    protected function install()
    {
        $command = static::composer('create-project laravel/laravel '.static::$appFolder.' --no-install --no-interaction --no-scripts --remove-vcs --prefer-dist');
        $process = Process::fromShellCommandline($command);
        $process->start();
        foreach ($process as $data) {
            static::$output->writeln($data);
        }

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        static::setTargetDisk(static::$appFolder);
    }
}
