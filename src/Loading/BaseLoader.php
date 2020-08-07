<?php declare(strict_types = 1);

namespace Orisai\Installer\Loading;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Installer\Config\PackageConfig;
use Orisai\Installer\Plugin;
use function sprintf;

abstract class BaseLoader
{

	public const SCHEMA_ITEM_FILE = 'file';
	public const SCHEMA_ITEM_SWITCHES = 'switches';
	public const META_ITEM_DIR = 'dir';

	/** @var array<mixed> */
	protected $schema = [];

	/** @var array<bool> */
	protected $switches = [];

	/** @var array<mixed> */
	protected $modulesMeta = [];

	final public function __construct()
	{
		// Disallow method override so it's safe to create magically
	}

	/**
	 * @return array<string>
	 */
	public function loadConfigFiles(string $rootDir): array
	{
		$resolved = [];

		foreach ($this->schema as $item) {
			foreach ($item[self::SCHEMA_ITEM_SWITCHES] ?? [] as $switchName => $switchValue) {
				// One of switches values does not match, config file not included
				if ($switchValue !== $this->switches[$switchName]) {
					continue 2;
				}
			}

			$resolved[] = $rootDir . '/' . $item[self::SCHEMA_ITEM_FILE];
		}

		return $resolved;
	}

	public function configureSwitch(string $switch, bool $value): void
	{
		if (!isset($this->switches[$switch])) {
			throw new InvalidArgument(sprintf(
				'Switch \'%s\' is not defined by any of loaded \'%s\' in \'%s\' section.',
				$switch,
				Plugin::DEFAULT_FILE_NAME,
				PackageConfig::SWITCHES_OPTION
			));
		}

		$this->switches[$switch] = $value;
	}

	/**
	 * @return array<mixed>
	 */
	public function loadModulesMeta(string $rootDir): array
	{
		$meta = [];

		foreach ($this->modulesMeta as $moduleName => $moduleMeta) {
			$dir = $moduleMeta[self::META_ITEM_DIR];
			$moduleMeta[self::META_ITEM_DIR] = $dir === '' ? $rootDir : $rootDir . '/' . $dir;

			$meta[$moduleName] = $moduleMeta;
		}

		return $meta;
	}

}
