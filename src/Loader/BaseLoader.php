<?php declare(strict_types = 1);

namespace Orisai\Installer\Loader;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Message;
use Orisai\Installer\SchemaName;
use function array_keys;
use function implode;

abstract class BaseLoader
{

	/** @internal */
	public const
		SchemaItemFile = 'file',
		SchemaItemSwitches = 'switches';

	/** @internal */
	public const MetaItemDir = 'dir';

	/** @var array<int, array{file: string, switches?: array<string, bool>}> */
	protected array $schema = [];

	/** @var array<string, bool> */
	protected array $switches = [];

	/** @var array<string, array{dir: string}> */
	protected array $modules = [];

	/**
	 * @return array<int, string>
	 */
	public function loadConfigFiles(string $rootDir): array
	{
		$resolved = [];

		foreach ($this->schema as $item) {
			foreach ($item[self::SchemaItemSwitches] ?? [] as $switchName => $switchValue) {
				// One of switches values does not match, config file not included
				if ($switchValue !== $this->switches[$switchName]) {
					continue 2;
				}
			}

			$resolved[] = $rootDir . '/' . $item[self::SchemaItemFile];
		}

		return $resolved;
	}

	public function configureSwitch(string $switch, bool $value, bool $throwOnMissing = true): void
	{
		if (!isset($this->switches[$switch])) {
			if (!$throwOnMissing) {
				return;
			}

			$schemaName = SchemaName::DefaultName;
			$switchesInline = implode(', ', array_keys($this->switches));
			$message = Message::create()
				->withContext("Trying to set value of switch '$switch'.")
				->withProblem("Switch is not defined by any of loaded '$schemaName'.")
				->withSolution("Do not configure switch or choose one of available: '$switchesInline'.");

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

		foreach ($this->modules as $moduleName => $moduleMeta) {
			$dir = $moduleMeta[self::MetaItemDir];
			$moduleMeta[self::MetaItemDir] = $dir === '' ? $rootDir : $rootDir . '/' . $dir;

			$meta[$moduleName] = $moduleMeta;
		}

		return $meta;
	}

}
