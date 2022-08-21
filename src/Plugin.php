<?php declare(strict_types = 1);

namespace Orisai\Installer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Orisai\Installer\Console\CommandProvider;
use Orisai\Installer\Loader\LoaderGenerator;
use Orisai\Installer\Modules\ModuleSchemaLocator;
use Orisai\Installer\Modules\ModulesGenerator;
use Orisai\Installer\Packages\PackagesDataGenerator;
use function implode;

/**
 * @internal
 */
final class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{

	public static function getSubscribedEvents(): array
	{
		return [
			ScriptEvents::POST_INSTALL_CMD => 'install',
			ScriptEvents::POST_UPDATE_CMD => 'update',
			PackageEvents::POST_PACKAGE_UNINSTALL => 'remove',
		];
	}

	public function getCapabilities(): array
	{
		return [
			CommandProviderCapability::class => CommandProvider::class,
		];
	}

	public function activate(Composer $composer, IOInterface $io): void
	{
		// Must be implemented
	}

	public function deactivate(Composer $composer, IOInterface $io): void
	{
		// Must be implemented
	}

	public function uninstall(Composer $composer, IOInterface $io): void
	{
		// Must be implemented
	}

	public function install(Event $event): void
	{
		$this->generateLoader($event->getComposer(), $event->getIO());
	}

	public function update(Event $event): void
	{
		$this->generateLoader($event->getComposer(), $event->getIO());
	}

	public function remove(PackageEvent $event): void
	{
		$this->generateLoader($event->getComposer(), $event->getIO());
	}

	private function generateLoader(Composer $composer, IOInterface $io): void
	{
		$dataGenerator = new PackagesDataGenerator($composer);
		$data = $dataGenerator->generate();
		$rootPackage = $data->getRootPackage();

		$locator = new ModuleSchemaLocator();
		$paths = [];
		$schema = $locator->locate($rootPackage, null, $paths);

		if ($schema === null) {
			$pathsInline = implode(', ', $paths);
			$io->write(
				'<info>orisai/installer: </info>Installation skipped, ' .
				"schema file is missing (one of $pathsInline).",
			);

			return;
		}

		$modulesGenerator = new ModulesGenerator();
		$modules = $modulesGenerator->generate($data, $schema);

		$loaderGenerator = new LoaderGenerator($modules);
		$loader = $loaderGenerator->generateAndSave();

		$io->write(
			"<info>orisai/installer: </info>Generated loader <info>{$loader->getClass()}</info>.",
		);
	}

}
