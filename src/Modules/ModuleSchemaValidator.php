<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;
use Symfony\Component\Filesystem\Path;
use function file_exists;

/**
 * @internal
 */
final class ModuleSchemaValidator
{

	public function validate(ModuleSchema $schema, PackageData $data): void
	{
		foreach ($schema->getConfigFiles() as $file) {
			$absolutePath = $file->getAbsolutePath();
			if (!file_exists($absolutePath)) {
				$relativePath = Path::makeRelative($absolutePath, $data->getAbsolutePath());

				throw InvalidArgument::create()
					->withMessage("Config file '$relativePath' not found in package '{$data->getName()}'.");
			}
		}
	}

}
