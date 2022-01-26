<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

final class ConfigFileSchema
{

	private string $absolutePath;

	private ConfigFilePriority $priority;

	/** @var array<int, string> */
	private array $requiredPackages = [];

	/** @var array<string, bool> */
	private array $requiredSwitchValues = [];

	/**
	 * @internal
	 */
	public function __construct(string $absolutePath)
	{
		$this->absolutePath = $absolutePath;
		$this->priority = ConfigFilePriority::normal();
	}

	public function setPriority(ConfigFilePriority $priority): void
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
	public function getAbsolutePath(): string
	{
		return $this->absolutePath;
	}

	/**
	 * @internal
	 */
	public function getPriority(): ConfigFilePriority
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
