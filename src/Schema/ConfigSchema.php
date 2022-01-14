<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

final class ConfigSchema
{

	private string $file;

	private ConfigPriority $priority;

	/** @var array<int, string> */
	private array $requiredPackages = [];

	/** @var array<string, bool> */
	private array $requiredSwitchValues = [];

	/**
	 * @internal
	 */
	public function __construct(string $file)
	{
		$this->file = $file;
		$this->priority = ConfigPriority::normal();
	}

	public function setPriority(ConfigPriority $priority): void
	{
		$this->priority = $priority;
	}

	public function addRequiredPackage(string $package): void
	{
		$this->requiredPackages[] = $package;
	}

	public function setRequiredSwitchValue(string $switch, bool $value): void
	{
		$this->requiredSwitchValues[$switch] = $value;
	}

	/**
	 * @internal
	 */
	public function getFile(): string
	{
		return $this->file;
	}

	/**
	 * @internal
	 */
	public function getPriority(): ConfigPriority
	{
		return $this->priority;
	}

	/**
	 * @return array<int, string>
	 *
	 * @internal
	 */
	public function getRequiredPackages(): array
	{
		return $this->requiredPackages;
	}

	/**
	 * @return array<string, bool>
	 *
	 * @internal
	 */
	public function getRequiredSwitchValues(): array
	{
		return $this->requiredSwitchValues;
	}

}
