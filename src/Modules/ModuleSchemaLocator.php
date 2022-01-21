<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;
use Orisai\Installer\SchemaName;
use function file_exists;
use function get_debug_type;

final class ModuleSchemaLocator
{

	public function locate(PackageData $data, ?string $schemaRelativeName = null): ?ModuleSchema
	{
		$schemaRelativeName ??= SchemaName::DEFAULT_NAME;

		$schemaFqn = "{$data->getAbsolutePath()}/$schemaRelativeName";
		if (!file_exists($schemaFqn)) {
			return null;
		}

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

	public function locateOrThrow(PackageData $data, ?string $schemaRelativeName = null): ModuleSchema
	{
		$schema = $this->locate($data, $schemaRelativeName);

		if ($schema !== null) {
			return $schema;
		}

		throw InvalidState::create()
			->withMessage("Package '{$data->getName()}' does not have config file '$schemaRelativeName'.");
	}

}
