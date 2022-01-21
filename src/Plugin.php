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

/**
 * @internal
 */
final class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{

	public const DEFAULT_FILE_NAME = 'orisai.php';

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			ScriptEvents::POST_INSTALL_CMD => 'install',
			ScriptEvents::POST_UPDATE_CMD => 'update',
			PackageEvents::POST_PACKAGE_UNINSTALL => 'remove',
		];
	}

	/**
	 * {@inheritDoc}
	 */
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
		$this->generateLoader($event->getComposer());
	}

	public function update(Event $event): void
	{
		$this->generateLoader($event->getComposer());
	}

	public function remove(PackageEvent $event): void
	{
		$this->generateLoader($event->getComposer());
	}

	private function generateLoader(Composer $composer): void
	{
		$dataGenerator = new PackagesDataGenerator($composer);
		$data = $dataGenerator->generate();
		$rootPackage = $data->getRootPackage();

		$locator = new ModuleSchemaLocator();
		$schema = $locator->locate($rootPackage);

		if ($schema === null) {
			return;
		}

		$modulesGenerator = new ModulesGenerator();
		$modules = $modulesGenerator->generate($data, $schema);

		$loaderGenerator = new LoaderGenerator($modules);
		$loaderGenerator->generateAndSave();
	}

}
