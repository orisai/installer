<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

use Composer\Package\PackageInterface;
use function is_string;
use function strrpos;
use function substr;

final class PackageConfig
{

	public const
		VERSION_OPTION = 'version',
		LOADER_OPTION = 'loader',
		CONFIGS_OPTION = 'configs',
		SWITCHES_OPTION = 'switches',
		IGNORE_OPTION = 'ignore',
		SIMULATED_MODULES_OPTION = 'simulated-modules';

	private string $schemaPath;

	private string $schemaFile;

	private float $version;

	private ?LoaderConfig $loader;

	/** @var array<FileConfig> */
	private array $configs;

	/** @var array<bool> */
	private array $switches;

	/** @var array<string> */
	private array $ignoredPackages;

	/** @var array<SimulatedModuleConfig> */
	private array $simulatedModules;

	private PackageInterface $package;

	/**
	 * @param array<mixed> $config
	 */
	public function __construct(array $config, PackageInterface $package, string $schemaFile)
	{
		$lastSlashPosition = strrpos($schemaFile, '/');
		$this->schemaPath = $lastSlashPosition === false ? '' : substr($schemaFile, 0, $lastSlashPosition);
		$this->schemaFile = $schemaFile;
		$this->version = $config[self::VERSION_OPTION];
		$this->configs = $this->normalizeConfigs($config[self::CONFIGS_OPTION]);
		$this->switches = $config[self::SWITCHES_OPTION];
		$this->loader = $config[self::LOADER_OPTION] !== null ? new LoaderConfig($config[self::LOADER_OPTION]) : null;
		$this->ignoredPackages = $config[self::IGNORE_OPTION];
		$this->simulatedModules = $this->normalizeSimulatedModules($config[self::SIMULATED_MODULES_OPTION]);
		$this->package = $package;
	}

	public function getSchemaPath(): string
	{
		return $this->schemaPath;
	}

	public function getSchemaFile(): string
	{
		return $this->schemaFile;
	}

	public function getVersion(): float
	{
		return $this->version;
	}

	public function getLoader(): ?LoaderConfig
	{
		return $this->loader;
	}

	/**
	 * @return array<FileConfig>
	 */
	public function getConfigs(): array
	{
		return $this->configs;
	}

	/**
	 * @return array<bool>
	 */
	public function getSwitches(): array
	{
		return $this->switches;
	}

	/**
	 * @return array<string>
	 */
	public function getIgnoredPackages(): array
	{
		return $this->ignoredPackages;
	}

	/**
	 * @return array<SimulatedModuleConfig>
	 */
	public function getSimulatedModules(): array
	{
		return $this->simulatedModules;
	}

	public function getPackage(): PackageInterface
	{
		return $this->package;
	}

	/**
	 * @param array<mixed> $files
	 * @return array<FileConfig>
	 */
	private function normalizeConfigs(array $files): array
	{
		$normalized = [];

		foreach ($files as $file) {
			if (is_string($file)) {
				$file = [
					FileConfig::FILE_OPTION => $file,
					FileConfig::SWITCHES_OPTION => [],
					FileConfig::PACKAGES_OPTION => [],
					FileConfig::PRIORITY_OPTION => FileConfig::PRIORITY_DEFAULT,
				];
			}

			$normalized[] = new FileConfig($file);
		}

		return $normalized;
	}

	/**
	 * @param array<mixed> $modules
	 * @return array<SimulatedModuleConfig>
	 */
	private function normalizeSimulatedModules(array $modules): array
	{
		$normalized = [];

		foreach ($modules as $name => $module) {
			if (is_string($module)) {
				$module = [
					SimulatedModuleConfig::NAME_OPTION => $name,
					SimulatedModuleConfig::PATH_OPTION => $module,
					SimulatedModuleConfig::OPTIONAL_OPTION => SimulatedModuleConfig::OPTIONAL_DEFAULT,
				];
			} else {
				$module[SimulatedModuleConfig::NAME_OPTION] = $name;
			}

			$normalized[] = new SimulatedModuleConfig($module);
		}

		return $normalized;
	}

}
