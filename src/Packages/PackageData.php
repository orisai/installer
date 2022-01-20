<?php declare(strict_types = 1);

namespace Orisai\Installer\Packages;

/**
 * @internal
 */
final class PackageData
{

	private string $name;

	/** @var array<PackageLink> */
	private array $requires;

	/** @var array<PackageLink> */
	private array $devRequires;

	/** @var array<PackageLink> */
	private array $replaces;

	private string $absolutePath;

	private string $relativePath;

	/**
	 * @param array<PackageLink> $requires
	 * @param array<PackageLink> $devRequires
	 * @param array<PackageLink> $replaces
	 */
	public function __construct(
		string $name,
		array $requires,
		array $devRequires,
		array $replaces,
		string $absolutePath,
		string $relativePath
	)
	{
		$this->name = $name;
		$this->requires = $requires;
		$this->devRequires = $devRequires;
		$this->replaces = $replaces;
		$this->absolutePath = $absolutePath;
		$this->relativePath = $relativePath;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return array<PackageLink>
	 */
	public function getRequires(): array
	{
		return $this->requires;
	}

	/**
	 * @return array<PackageLink>
	 */
	public function getDevRequires(): array
	{
		return $this->devRequires;
	}

	/**
	 * @return array<PackageLink>
	 */
	public function getReplaces(): array
	{
		return $this->replaces;
	}

	public function getAbsolutePath(): string
	{
		return $this->absolutePath;
	}

	public function getRelativePath(): string
	{
		return $this->relativePath;
	}

}
