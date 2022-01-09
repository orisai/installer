<?php declare(strict_types = 1);

namespace Orisai\Installer\Monorepo;

use Composer\Package\CompletePackage;

/**
 * @internal
 */
final class SimulatedPackage extends CompletePackage
{

	private string $packageDirectory;

	private string $parentName;

	public function getPackageDirectory(): string
	{
		return $this->packageDirectory;
	}

	public function setPackageDirectory(string $packageDirectory): void
	{
		$this->packageDirectory = $packageDirectory;
	}

	public function getParentName(): string
	{
		return $this->parentName;
	}

	public function setParentName(string $parentName): void
	{
		$this->parentName = $parentName;
	}

}
