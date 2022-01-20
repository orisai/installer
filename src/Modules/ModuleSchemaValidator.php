<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;
use function get_debug_type;

/**
 * @internal
 */
final class ModuleSchemaValidator
{

	public function validate(PackageData $data, string $schemaFqn, string $schemaRelativeName): ModuleSchema
	{
		$schema = require $schemaFqn;

		$schemaClass = ModuleSchema::class;
		if (!$schema instanceof $schemaClass) {
			$configType = get_debug_type($schema);

			throw InvalidArgument::create()
				->withMessage(
					"Package '{$data->getName()}' config file '$schemaRelativeName' " .
					"has to return instance of '$schemaClass', '$configType' returned.",
				);
		}

		return $schema;
	}

}
