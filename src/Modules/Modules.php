<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

/**
 * @internal
 */
final class Modules
{

	private Module $rootModule;

	/** @var array<string, Module> */
	private array $modules;

	/**
	 * @param array<string, Module> $modules
	 */
	public function __construct(Module $rootModule, array $modules)
	{
		$this->rootModule = $rootModule;
		$this->modules = $modules;
	}

	public function getRootModule(): Module
	{
		return $this->rootModule;
	}

	/**
	 * @return array<string, Module>
	 */
	public function getModules(): array
	{
		return $this->modules;
	}

	public function getModule(string $name): ?Module
	{
		return $this->modules[$name] ?? null;
	}

}
