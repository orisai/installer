<?php declare(strict_types = 1);

namespace Orisai\Installer\Tester;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Installer\Loader\BaseLoader;
use Orisai\Installer\Loader\LoaderGenerator;
use Orisai\Installer\Modules\ModuleSchemaLocator;
use Orisai\Installer\Modules\ModuleSchemaMerger;
use Orisai\Installer\Modules\ModuleSchemaValidator;
use Orisai\Installer\Modules\ModulesGenerator;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Packages\PackagesDataStorage;
use Orisai\Installer\Schema\ModuleSchema;
use Symfony\Component\Filesystem\Path;
use function file_exists;
use function sprintf;

final class ModuleTester
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

	public function validateModule(?string $schemaFqn = null, ?string $packageName = null): void
	{
		$data = PackagesDataStorage::load();

		if ($packageName === null) {
			$package = $data->getRootPackage();
		} else {
			$package = $data->getPackage($packageName);

			if ($package === null) {
				throw InvalidArgument::create()
					->withMessage(sprintf('Package \'%s\' does not exists', $packageName));
			}
		}

		$schemaRelativeName = $schemaFqn !== null
			? $this->getSchemaRelativeName($schemaFqn, $package)
			: null;

		$locator = new ModuleSchemaLocator();
		$schema = $locator->locateOrThrow($package, $schemaRelativeName);

		$validator = new ModuleSchemaValidator();
		$validator->validate($schema);
	}

	private function getSchemaRelativeName(string $schemaFqn, PackageData $package): string
	{
		if (!file_exists($schemaFqn)) {
			throw InvalidArgument::create()
				->withMessage('File does not exist.');
		}

		return Path::makeRelative($schemaFqn, $package->getAbsolutePath());
	}

}
