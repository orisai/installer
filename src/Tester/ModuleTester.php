<?php declare(strict_types = 1);

namespace Orisai\Installer\Tester;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Installer\Loader\BaseLoader;
use Orisai\Installer\Loader\LoaderGenerator;
use Orisai\Installer\Modules\ModuleSchemaValidator;
use Orisai\Installer\Modules\ModulesGenerator;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Packages\PackagesDataStorage;
use Symfony\Component\Filesystem\Path;
use function file_exists;
use function sprintf;

final class ModuleTester
{

	public function generateLoader(string $schemaFqn): BaseLoader
	{
		$data = PackagesDataStorage::load();

		$schemaRelativeName = $this->getSchemaRelativeName($schemaFqn, $data->getRootPackage());

		$modulesGenerator = new ModulesGenerator();
		$modules = $modulesGenerator->generate($data, $schemaRelativeName);

		return (new LoaderGenerator($modules))->generate();
	}

	public function validateModule(string $schemaFqn, ?string $packageName = null): void
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

		$schemaRelativeName = $this->getSchemaRelativeName($schemaFqn, $package);

		$validator = new ModuleSchemaValidator();
		$validator->validate($package, $schemaFqn, $schemaRelativeName);
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
