# Lura - A Console Application Installer

Base package for console application installer.

## Install

```shell
composer global require norman-huth/lura
```

## Usage

### Install Installer

The package does not include an installer out of the box.

You need to install one or more installers.

Example: [norman-huth/laravel-installer](https://github.com/Muetze42/laravel-installer)

Do not forget to register the Installer after installation (`lura register norman-huth/laravel-installer`)

### Run Lura

```shell
lura 
```

### Edit Installer Config

Use this command to get the path to your local config file:

```shell
lura config:file
```

### Register Installed Installer

```shell
lura register vendor/name
```

### Clear Lura Cache

```shell
lura cache:clear
```

### Create Installer

Use this template: [lura-installer-template](https://github.com/Muetze42/lura-installer-template)

### Upgrade 1 to 2

Remove the `int` return of the `runLura` method.

### Upgrade 3

Replace the `NormanHuth\ConsoleApp` with `NormanHuth\Lura`.

---

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://vshymanskyy.github.io/StandWithUkraine/)

[![Woman. Life. Freedom.](https://raw.githubusercontent.com/Muetze42/Muetze42/2033b219c6cce0cb656c34da5246434c27919bcd/files/iran-banner-big.svg)](https://linktr.ee/CurrentPetitionsFreeIran)
