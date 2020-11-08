<?php declare(strict_types = 1);

namespace Orisai\Installer;

use OriNette\DI\Boot\BaseConfigurator;
use Orisai\Installer\Loading\BaseLoader as ModuleLoader;

final class AutomaticConfigurator extends BaseConfigurator
{

	private ModuleLoader $loader;

	public function __construct(string $rootDir, ModuleLoader $loader)
	{
		parent::__construct($rootDir);
		$this->loader = $loader;
	}

	/**
	 * @return array<mixed>
	 */
	protected function getDefaultParameters(): array
	{
		$parameters = parent::getDefaultParameters();
		$parameters['modules'] = $this->loader->loadModulesMeta($this->rootDir);

		return $parameters;
	}

	/**
	 * @return array<string>
	 */
	protected function loadConfigFiles(): array
	{
		return $this->loader->loadConfigFiles($this->rootDir);
	}

	public function loadContainer(): string
	{
		$this->loader->configureSwitch('consoleMode', $this->staticParameters['consoleMode']);
		$this->loader->configureSwitch('debugMode', $this->staticParameters['debugMode']);

		return parent::loadContainer();
	}

}
