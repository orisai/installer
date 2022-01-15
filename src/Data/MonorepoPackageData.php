<?php declare(strict_types = 1);

namespace Orisai\Installer\Data;

use Orisai\Installer\Config\PackageConfig;
use Orisai\Installer\Resolving\Link;

/**
 * @internal
 */
final class MonorepoPackageData extends InstallablePackageData
{

	private InstallablePackageData $parent;

	/**
	 * @param array<Link> $requires
	 * @param array<Link> $devRequires
	 * @param array<Link> $replaces
	 */
	public function __construct(
		string $name,
		array $requires,
		array $devRequires,
		array $replaces,
		PackageConfig $config,
		string $relativePath,
		string $absolutePath,
		InstallablePackageData $parent
	)
	{
		parent::__construct($name, $requires, $devRequires, $replaces, $config, $relativePath, $absolutePath);
		$this->parent = $parent;
	}

	public function getParent(): InstallablePackageData
	{
		return $this->parent;
	}

}
