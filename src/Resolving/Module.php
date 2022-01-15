<?php declare(strict_types = 1);

namespace Orisai\Installer\Resolving;

use Orisai\Installer\Data\InstallablePackageData;

/**
 * @internal
 */
final class Module
{

	private InstallablePackageData $package;

	/** @var array<Module> */
	private array $dependents = [];

	public function __construct(InstallablePackageData $package)
	{
		$this->package = $package;
	}

	public function getPackage(): InstallablePackageData
	{
		return $this->package;
	}

	/**
	 * @param array<Module> $dependents
	 */
	public function setDependents(array $dependents): void
	{
		$this->dependents = $dependents;
	}

	/**
	 * @return array<Module>
	 */
	public function getDependents(): array
	{
		return $this->dependents;
	}

}
