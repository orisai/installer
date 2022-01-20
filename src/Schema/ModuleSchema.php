<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

final class ModuleSchema
{

	private ?LoaderSchema $loader = null;

	/** @var array<int, ConfigFileSchema> */
	private array $configFiles = [];

	/** @var array<string, bool> */
	private array $switches = [];

	/** @var array<int, SubmoduleSchema> */
	private array $monorepoPackages = [];

	public function setLoader(string $file, string $class): void
	{
		$this->loader = new LoaderSchema($file, $class);
	}

	public function addConfigFile(string $file): ConfigFileSchema
	{
		return $this->configFiles[] = new ConfigFileSchema($file);
	}

	public function addSwitch(string $name, bool $defaultValue): void
	{
		$this->switches[$name] = $defaultValue;
	}

	public function addSubmodule(string $name, string $path): SubmoduleSchema
	{
		return $this->monorepoPackages[] = new SubmoduleSchema($name, $path);
	}

	/**
	 * @internal
	 */
	public function getLoader(): ?LoaderSchema
	{
		return $this->loader;
	}

	/**
	 * @return array<int, ConfigFileSchema>
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

	/**
	 * @return array<int, SubmoduleSchema>
	 *
	 * @internal
	 */
	public function getMonorepoSubmodules(): array
	{
		return $this->monorepoPackages;
	}

}
