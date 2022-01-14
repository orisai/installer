<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

final class PackageSchema
{

	private ?LoaderSchema $loader = null;

	/** @var array<int, ConfigSchema> */
	private array $configs = [];

	/** @var array<string, bool> */
	private array $switches = [];

	/** @var array<int, string> */
	private array $ignorePackageConfigs = [];

	/** @var array<int, MonorepoSubpackageSchema> */
	private array $monorepoPackages = [];

	public function setLoader(string $file, string $class): void
	{
		$this->loader = new LoaderSchema($file, $class);
	}

	public function addConfig(string $file): ConfigSchema
	{
		return $this->configs[] = new ConfigSchema($file);
	}

	public function addSwitch(string $name, bool $defaultValue): void
	{
		$this->switches[$name] = $defaultValue;
	}

	public function ignoreConfigFrom(string $package): void
	{
		$this->ignorePackageConfigs[] = $package;
	}

	public function addMonorepoPackage(string $name, string $path): MonorepoSubpackageSchema
	{
		return $this->monorepoPackages[] = new MonorepoSubpackageSchema($name, $path);
	}

	/**
	 * @internal
	 */
	public function getLoader(): ?LoaderSchema
	{
		return $this->loader;
	}

	/**
	 * @return array<int, ConfigSchema>
	 *
	 * @internal
	 */
	public function getConfigs(): array
	{
		return $this->configs;
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
	 * @return array<int, string>
	 *
	 * @internal
	 */
	public function getIgnorePackageConfigs(): array
	{
		return $this->ignorePackageConfigs;
	}

	/**
	 * @return array<int, MonorepoSubpackageSchema>
	 *
	 * @internal
	 */
	public function getMonorepoPackages(): array
	{
		return $this->monorepoPackages;
	}

}
