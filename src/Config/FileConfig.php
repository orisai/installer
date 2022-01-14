<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

use Orisai\Installer\Schema\ConfigSchema;

/**
 * @internal
 */
final class FileConfig
{

	public const
		PRIORITY_VALUE_LOW = 'low',
		PRIORITY_VALUE_NORMAL = 'normal',
		PRIORITY_VALUE_HIGH = 'high';

	private string $file;

	private string $priority;

	/** @var array<bool> */
	private array $switches;

	/** @var array<string> */
	private array $packages;

	public function __construct(ConfigSchema $schema)
	{
		$this->file = $schema->getFile();
		$this->priority = $schema->getPriority()->name;
		$this->switches = $schema->getRequiredSwitchValues();
		$this->packages = $schema->getRequiredPackages();
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getPriority(): string
	{
		return $this->priority;
	}

	/**
	 * @return array<mixed>
	 */
	public function getSwitches(): array
	{
		return $this->switches;
	}

	/**
	 * @return array<string>
	 */
	public function getRequiredPackages(): array
	{
		return $this->packages;
	}

}
