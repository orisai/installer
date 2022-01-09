<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

/**
 * @internal
 */
final class FileConfig
{

	public const
		FILE_OPTION = 'file',
		SWITCHES_OPTION = 'switches',
		PACKAGES_OPTION = 'packages',
		PRIORITY_OPTION = 'priority';

	public const PRIORITY_DEFAULT = self::PRIORITY_VALUE_NORMAL;

	public const
		PRIORITY_VALUE_LOW = 'low',
		PRIORITY_VALUE_NORMAL = 'normal',
		PRIORITY_VALUE_HIGH = 'high';

	public const PRIORITIES = [
		self::PRIORITY_VALUE_LOW,
		self::PRIORITY_VALUE_NORMAL,
		self::PRIORITY_VALUE_HIGH,
	];

	private string $file;

	private string $priority;

	/** @var array<bool> */
	private array $switches;

	/** @var array<string> */
	private array $packages;

	/**
	 * @param array<mixed> $config
	 */
	public function __construct(array $config)
	{
		$this->file = $config[self::FILE_OPTION];
		$this->priority = $config[self::PRIORITY_OPTION];
		$this->switches = $config[self::SWITCHES_OPTION];
		$this->packages = $config[self::PACKAGES_OPTION];
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
