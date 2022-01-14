<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

use Composer\Package\PackageInterface;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Installer\Schema\PackageSchema;
use Orisai\Installer\Utils\PathResolver;
use function get_debug_type;

/**
 * @internal
 */
final class ConfigValidator
{

	private PathResolver $pathResolver;

	public function __construct(PathResolver $pathResolver)
	{
		$this->pathResolver = $pathResolver;
	}

	public function validateConfiguration(PackageInterface $package, string $unresolvedFileName): PackageConfig
	{
		$schemaFileFullName = $this->pathResolver->getSchemaFileFullName($package, $unresolvedFileName);
		$schemaFileRelativeName = $this->pathResolver->getSchemaFileRelativeName($package, $schemaFileFullName);

		$config = require $schemaFileFullName;

		$schemaClass = PackageSchema::class;
		if (!$config instanceof $schemaClass) {
			$configType = get_debug_type($config);

			throw InvalidArgument::create()
				->withMessage(
					"Package '{$package->getName()}' config file '$schemaFileRelativeName' " .
					"has to return instance of '$schemaClass', '$configType' returned.",
				);
		}

		return new PackageConfig($config, $package, $schemaFileRelativeName);
	}

}
