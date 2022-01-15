<?php declare(strict_types = 1);

namespace Orisai\Installer\Data;

use Orisai\Installer\Resolving\Link;

/**
 * @internal
 */
class PackageData
{

	private string $name;

	/** @var array<Link> */
	private array $requires;

	/** @var array<Link> */
	private array $devRequires;

	/** @var array<Link> */
	private array $replaces;

	/**
	 * @param array<Link> $requires
	 * @param array<Link> $devRequires
	 * @param array<Link> $replaces
	 */
	public function __construct(string $name, array $requires, array $devRequires, array $replaces)
	{
		$this->name = $name;
		$this->requires = $requires;
		$this->devRequires = $devRequires;
		$this->replaces = $replaces;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return array<Link>
	 */
	public function getRequires(): array
	{
		return $this->requires;
	}

	/**
	 * @return array<Link>
	 */
	public function getDevRequires(): array
	{
		return $this->devRequires;
	}

	/**
	 * @return array<Link>
	 */
	public function getReplaces(): array
	{
		return $this->replaces;
	}

}
