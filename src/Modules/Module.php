<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;

/**
 * @internal
 */
final class Module
{

	private ModuleSchema $schema;

	private PackageData $data;

	/** @var array<string, Module> */
	private array $dependents;

	public function __construct(ModuleSchema $schema, PackageData $data)
	{
		$this->schema = $schema;
		$this->data = $data;
	}

	public function getSchema(): ModuleSchema
	{
		return $this->schema;
	}

	public function getData(): PackageData
	{
		return $this->data;
	}

	/**
	 * @param array<string, Module> $dependents
	 */
	public function setDependents(array $dependents): void
	{
		$this->dependents = $dependents;
	}

	/**
	 * @return array<string, Module>
	 */
	public function getDependents(): array
	{
		return $this->dependents;
	}

}
