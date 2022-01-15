<?php declare(strict_types = 1);

namespace Orisai\Installer\Loading;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Message;
use function array_keys;
use function implode;
use function sprintf;

abstract class BaseLoader
{

	public const
		SCHEMA_ITEM_FILE = 'file',
		SCHEMA_ITEM_SWITCHES = 'switches';

	public const META_ITEM_DIR = 'dir';

	/** @var array<int, mixed> */
	protected array $schema = [];

	/** @var array<string, bool> */
	protected array $switches = [];

	/** @var array<string, mixed> */
	protected array $modulesMeta = [];

	final public function __construct()
	{
		// Disallow method override so it's safe to create magically
	}

	/**
	 * @return array<int, string>
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
			$message = Message::create()
				->withContext(sprintf('Trying to set value of switch `%s`.', $switch))
				->withProblem(sprintf('Switch is not defined by any of loaded `%s`.', 'orisai.php'))
				->withSolution(sprintf(
					'Do not configure switch or choose one of available: `%s`',
					implode(', ', array_keys($this->switches)),
				));

			throw InvalidArgument::create()
				->withMessage($message);
		}

		$this->switches[$switch] = $value;
	}

	/**
	 * @return array<string, mixed>
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
