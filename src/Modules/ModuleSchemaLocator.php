<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;
use Orisai\Installer\SchemaName;
use function file_exists;
use function get_debug_type;
use function implode;

final class ModuleSchemaLocator
{

	/**
	 * @param array<int, string> $triedPaths
	 */
	public function locate(
		PackageData $data,
		?string $schemaRelativeName = null,
		array &$triedPaths = []
	): ?ModuleSchema
	{
		if ($schemaRelativeName !== null) {
			return $this->getSchema($data, $schemaRelativeName);
		}

		foreach (SchemaName::FILE_LOCATIONS as $location) {
			$schema = $this->getSchema($data, $location);
			$triedPaths[] = $location;

			if ($schema !== null) {
				return $schema;
			}
		}

		return null;
	}

	public function locateOrThrow(PackageData $data, ?string $schemaRelativeName = null): ModuleSchema
	{
		$paths = [];
		$schema = $this->locate($data, $schemaRelativeName, $paths);

		if ($schema !== null) {
			return $schema;
		}

		$pathsInline = implode(', ', $paths);

		throw InvalidState::create()
			->withMessage(
				"Schema file is missing in '{$data->getName()}' (one of $pathsInline).",
			);
	}

	private function getSchema(PackageData $data, string $schemaRelativeName): ?ModuleSchema
	{
		$schemaFqn = "{$data->getAbsolutePath()}/$schemaRelativeName";

		if (!file_exists($schemaFqn)) {
			return null;
		}

		return $this->requireSchema($schemaFqn, $data, $schemaRelativeName);
	}

	private function requireSchema(string $schemaFqn, PackageData $data, ?string $schemaRelativeName): ModuleSchema
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
