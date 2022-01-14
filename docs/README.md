# Installer

[Composer](https://getcomposer.org) installer for [nette/di](https://github.com/nette/di/) based packages

## Content

- [Setup](#setup)
- [Schema](#schema)
- [Automatic configurator](#automatic-configurator)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/installer
```

## Schema

`orisai.php`

```php
use Orisai\Installer\Schema\ConfigFilePriority;
use Orisai\Installer\Schema\PackageSchema;

$schema = new PackageSchema();

// generated code with all packages and their configs
// root-only
$schema->setLoader(
	__DIR__ . '/src/Loader.php',
	App\Loader::class,
);

// config file provided by current package
$schema->addConfigFile(__DIR__ . '/src/wiring.neon');

$httpsConfig = $schema->addConfigFile(__DIR__ . '/src/https.neon');
// switch and its value required to include this config file
$httpsConfig->setRequiredSwitchValue('httpsOnly', true);
// package required to be installed to include this config file
$httpsConfig->addRequiredPackage('vendor/package');
// change loading priority of config file
// by default configs are ordered by package priority in dependency tree and added to 'normal' group
$httpsConfig->setPriority(ConfigFilePriority::normal());

// enable/disable config file by runtime switch
$schema->addSwitch('httpsOnly', false);

// packages which are part of monorepo are not really considered installed in monorepo
// this simulates their existence for purpose of in-monorepo development
// root-only
$schema->addMonorepoPackage('vendor/package', __DIR__ . '/packages/submodule-a')
	->setOptional(false);

return $schema;
```

## Automatic configurator

Automatic configurator is a replacement for [nette/bootstrap](https://github.com/nette/bootstrap) `Configurator` and
[orisai/nette-di](https://github.com/orisai/nette-di) `ManualConfigurator`.

API is exactly same as for `ManualConfigurator`, except the `addConfig()` method which is replaced by internal calls
to `Loader`. Check the [orisai/nette-di](https://github.com/orisai/nette-di) documentation first to learn how to use the configurator.

```php
use App\Boot\Loader;
use Orisai\Installer\AutomaticConfigurator;

$configurator = new AutomaticConfigurator($rootDir, new Loader());
```

In DI are available additional parameters in `modules` key. They contain useful info about installed modules.

Installer config file switches `consoleMode` and `debugMode` switches are pre-configured to always load right configuration subset.
