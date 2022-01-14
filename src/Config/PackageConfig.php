<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

use Composer\Package\PackageInterface;
use Orisai\Installer\Schema\ConfigSchema;
use Orisai\Installer\Schema\MonorepoSubpackageSchema;
use Orisai\Installer\Schema\PackageSchema;
use function array_map;
use function strrpos;
use function substr;

/**
 * @internal
 */
final class PackageConfig
{

	private string $schemaPath;

	private string $schemaFile;

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

	public function __construct(PackageSchema $schema, PackageInterface $package, string $schemaFile)
	{
		$lastSlashPosition = strrpos($schemaFile, '/');
		$this->schemaPath = $lastSlashPosition === false ? '' : substr($schemaFile, 0, $lastSlashPosition);
		$this->schemaFile = $schemaFile;

		$this->configs = array_map(
			static fn (ConfigSchema $configSchema): FileConfig => new FileConfig($configSchema),
			$schema->getConfigs(),
		);

		$this->switches = $schema->getSwitches();

		$loader = $schema->getLoader();
		$this->loader = $loader !== null ? new LoaderConfig($loader) : null;

		$this->ignoredPackages = $schema->getIgnorePackageConfigs();

		$this->simulatedModules = array_map(
			static fn (MonorepoSubpackageSchema $subpackageSchema): SimulatedModuleConfig => new SimulatedModuleConfig(
				$subpackageSchema,
			),
			$schema->getMonorepoPackages(),
		);

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

}
