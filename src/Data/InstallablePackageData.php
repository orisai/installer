<?php declare(strict_types = 1);

namespace Orisai\Installer\Data;

use Orisai\Installer\Config\PackageConfig;
use Orisai\Installer\Resolving\Link;

/**
 * @internal
 */
class InstallablePackageData extends PackageData
{

	private PackageConfig $config;

	private string $relativePath;

	private string $absolutePath;

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
		string $absolutePath
	)
	{
		parent::__construct($name, $requires, $devRequires, $replaces);
		$this->config = $config;
		$this->relativePath = $relativePath;
		$this->absolutePath = $absolutePath;
	}

	public function getConfig(): PackageConfig
	{
		return $this->config;
	}

	public function getRelativePath(): string
	{
		return $this->relativePath;
	}

	public function getAbsolutePath(): string
	{
		return $this->absolutePath;
	}

}
