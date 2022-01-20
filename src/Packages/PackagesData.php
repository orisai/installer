<?php declare(strict_types = 1);

namespace Orisai\Installer\Packages;

/**
 * @internal
 */
final class PackagesData
{

	private PackageData $rootPackage;

	/** @var array<string, PackageData> */
	private array $packages = [];

	public function __construct(PackageData $rootPackage)
	{
		$this->rootPackage = $rootPackage;
		$this->addPackage($rootPackage);
	}

	public function getRootPackage(): PackageData
	{
		return $this->rootPackage;
	}

	public function addPackage(PackageData $package): void
	{
		$this->packages[$package->getName()] = $package;
	}

	/**
	 * @return array<string, PackageData>
	 */
	public function getPackages(): array
	{
		return $this->packages;
	}

	public function getPackage(string $name): ?PackageData
	{
		return $this->packages[$name] ?? null;
	}

}
