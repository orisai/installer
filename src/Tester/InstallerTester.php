<?php declare(strict_types = 1);

namespace Orisai\Installer\Tester;

use Orisai\Installer\Loader\BaseLoader;
use Orisai\Installer\Loader\LoaderGenerator;
use Orisai\Installer\Modules\ModuleSchemaLocator;
use Orisai\Installer\Modules\ModuleSchemaMerger;
use Orisai\Installer\Modules\ModulesGenerator;
use Orisai\Installer\Packages\PackagesDataStorage;
use Orisai\Installer\Schema\ModuleSchema;

final class InstallerTester
{

	public function generateLoader(ModuleSchema $schema): BaseLoader
	{
		$data = PackagesDataStorage::load();

		$locator = new ModuleSchemaLocator();
		$parentSchema = $locator->locate($data->getRootPackage());
		if ($parentSchema !== null) {
			$merger = new ModuleSchemaMerger();
			$schema = $merger->merge($parentSchema, $schema);
		}

		$modulesGenerator = new ModulesGenerator();
		$modules = $modulesGenerator->generate($data, $schema);

		return (new LoaderGenerator($modules))->generate();
	}

}
