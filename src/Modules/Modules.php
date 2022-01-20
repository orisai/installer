<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

/**
 * @internal
 */
final class Modules
{

	private string $rootDir;

	private Module $rootModule;

	/** @var array<string, Module> */
	private array $modules;

	/**
	 * @param array<string, Module> $modules
	 */
	public function __construct(string $rootDir, Module $rootModule, array $modules)
	{
		$this->rootDir = $rootDir;
		$this->rootModule = $rootModule;
		$this->modules = $modules;
	}

	public function getRootDir(): string
	{
		return $this->rootDir;
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
