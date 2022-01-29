<?php declare(strict_types = 1);

namespace Orisai\Installer\Loader;

/**
 * @internal
 */
final class DynamicLoader extends BaseLoader
{

	/**
	 * @param array<int, mixed>    $schema
	 * @param array<string, bool>  $switches
	 * @param array<string, mixed> $modules
	 */
	public function __construct(array $schema, array $switches, array $modules)
	{
		$this->schema = $schema;
		$this->switches = $switches;
		$this->modules = $modules;
	}

}
