<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Packages\PackagesData;
use Orisai\Installer\Schema\ModuleSchema;

/**
 * @internal
 */
final class ModulesGenerator
{

	private ModuleSchemaValidator $validator;

	private ModuleSchemaLocator $locator;

	private ModuleSorter $sorter;

	public function __construct()
	{
		$this->validator = new ModuleSchemaValidator();
		$this->locator = new ModuleSchemaLocator();
		$this->sorter = new ModuleSorter();
	}

	public function generate(PackagesData $data, ModuleSchema $rootSchema): Modules
	{
		$rootPackage = $data->getRootPackage();
		$rootModule = $this->dataToModule($rootPackage, $rootSchema);

		$modules = [];
		foreach ($data->getPackages() as $package) {
			$schema = $this->locator->locate($package);
			if ($schema === null) {
				continue;
			}

			$modules[$package->getName()] = $this->dataToModule($package, $schema);
		}

		$modules[$rootPackage->getName()] = $rootModule;

		return new Modules(
			$rootModule,
			$this->sorter->getSortedModules($modules, $data),
		);
	}

	private function dataToModule(PackageData $data, ModuleSchema $schema): Module
	{
		$this->validator->validate($schema);

		return new Module($schema, $data);
	}

}
