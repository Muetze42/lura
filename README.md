[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://vshymanskyy.github.io/StandWithUkraine/)

# Lura

An command line installer and generator kit.  
**Currently still under development.**

_Supported_

* :white_check_mark: Laravel
* :white_check_mark: Laravel Breeze
* :white_check_mark: Laravel Jetstream
* :white_check_mark: Laravel Nova
* :white_check_mark: Laravel Package
* :black_square_button: (Illuminate) Standalone Packages _(In development)_<br>Current available:
    * [illuminate/cache](https://github.com/illuminate/cache)
    * [illuminate/database](https://github.com/illuminate/database)
    * [illuminate/filesystem](https://github.com/illuminate/filesystem)
    * [illuminate/support](https://github.com/illuminate/support)
    * [illuminate/validation](https://github.com/illuminate/validation)
    * [illuminate/view](https://github.com/illuminate/view)
* :white_square_button: Statamic _(In planning)_
* :white_square_button: Symfony _(In planning)_
* :white_square_button: Pimcore _(In planning)_
* :white_square_button: CoreShop _(In planning)_

## Installation

```bash
composer global require norman-huth/lura
```

## Usage

### Create a app, package etc

```bash
lura create
```

Alternative:

```bash
lura new
```

### Install a (standalone) package

```bash
lura install
```

### Show current config

```bash
lura config
```

### Set config

```bash
lura config set custom-app-path /path-to-/folder
```

Array

```bash
lura config set default-author.homepage https://huth.it
```

## Documentation

Still comes everything ;) 🙃
