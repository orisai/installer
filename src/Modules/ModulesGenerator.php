<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Packages\PackagesData;
use Orisai\Installer\SchemaName;
use function assert;
use function file_exists;

/**
 * @internal
 */
final class ModulesGenerator
{

	private ModuleSchemaValidator $validator;

	private ModuleSorter $sorter;

	public function __construct()
	{
		$this->validator = new ModuleSchemaValidator();
		$this->sorter = new ModuleSorter();
	}

	public function generate(PackagesData $data, string $schemaRelativeName): Modules
	{
		$rootPackage = $data->getRootPackage();
		$rootModule = $this->dataToModule($rootPackage, $schemaRelativeName);
		assert($rootModule !== null);

		$modules = [];
		foreach ($data->getPackages() as $package) {
			$module = $this->dataToModule($package, SchemaName::DEFAULT_NAME);
			if ($module !== null) {
				$modules[$package->getName()] = $module;
			}
		}

		$modules[$rootPackage->getName()] = $rootModule;

		return new Modules(
			$data->getRootDir(),
			$rootModule,
			$this->sorter->getSortedModules($modules, $data),
		);
	}

	private function dataToModule(PackageData $data, string $schemaRelativeName): ?Module
	{
		$schemaFqn = "{$data->getAbsolutePath()}/$schemaRelativeName";
		if (!file_exists($schemaFqn)) {
			return null;
		}

		$schema = $this->validator->validate($data, $schemaFqn, $schemaRelativeName);

		return new Module($schemaRelativeName, $schema, $data);
	}

}
