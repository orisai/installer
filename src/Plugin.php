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
use Orisai\Installer\Command\CommandProvider;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Files\NeonReader;
use Orisai\Installer\Files\Writer;
use Orisai\Installer\Loading\LoaderGenerator;
use Orisai\Installer\Utils\PathResolver;
use Orisai\Installer\Utils\PluginActivator;

final class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{

	public const DEFAULT_FILE_NAME = 'orisai.neon';

	/**
	 * @return array<string>
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
	 * @return array<string>
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
		$pathResolver = new PathResolver($composer);
		$validator = new ConfigValidator(new NeonReader(), $pathResolver);
		$activator = new PluginActivator(
			$composer->getPackage(),
			$validator,
			$pathResolver,
			self::DEFAULT_FILE_NAME,
		);

		if (!$activator->isEnabled()) {
			return;
		}

		$loaderGenerator = new LoaderGenerator(
			$composer->getRepositoryManager()->getLocalRepository(),
			new Writer(),
			$pathResolver,
			$validator,
			$activator->getRootPackageConfiguration(),
		);

		$loaderGenerator->generateLoader();
	}

}
