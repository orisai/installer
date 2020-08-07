<?php declare(strict_types = 1);

namespace Orisai\Installer\Resolving;

use Orisai\Installer\Config\PackageConfig;

final class Module
{

	/** @var PackageConfig */
	private $config;

	/** @var array<Module> */
	private $dependents = [];

	public function __construct(PackageConfig $config)
	{
		$this->config = $config;
	}

	public function getConfiguration(): PackageConfig
	{
		return $this->config;
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
