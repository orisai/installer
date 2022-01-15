<?php declare(strict_types = 1);

namespace Orisai\Installer\Data;

/**
 * @internal
 */
final class InstallerData
{

	private string $rootDir;

	private InstallablePackageData $rootPackage;

	/** @var array<string, PackageData> */
	private array $packages = [];

	public function __construct(string $rootDir, InstallablePackageData $rootPackage)
	{
		$this->rootDir = $rootDir;
		$this->rootPackage = $rootPackage;
		$this->addPackage($rootPackage);
	}

	public function getRootDir(): string
	{
		return $this->rootDir;
	}

	public function getRootPackage(): InstallablePackageData
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
