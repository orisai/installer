<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

use Symfony\Component\Filesystem\Path;

final class ModuleSchema
{

	private ?LoaderSchema $loader = null;

	/** @var array<string, ConfigFileSchema> */
	private array $configFiles = [];

	/** @var array<string, bool> */
	private array $switches = [];

	public function setLoader(string $file, string $class): void
	{
		$this->loader = new LoaderSchema($file, $class);
	}

	public function addConfigFile(string $absolutePath): ConfigFileSchema
	{
		$canonical = Path::canonicalize($absolutePath);

		return $this->configFiles[$canonical] = new ConfigFileSchema($canonical);
	}

	public function addSwitch(string $name, bool $defaultValue): void
	{
		$this->switches[$name] = $defaultValue;
	}

	/**
	 * @internal
	 */
	public function getLoader(): ?LoaderSchema
	{
		return $this->loader;
	}

	/**
	 * @return array<string, ConfigFileSchema>
	 *
	 * @internal
	 */
	public function getConfigFiles(): array
	{
		return $this->configFiles;
	}

	/**
	 * @return array<string, bool>
	 *
	 * @internal
	 */
	public function getSwitches(): array
	{
		return $this->switches;
	}

}
