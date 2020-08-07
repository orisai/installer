<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

use Composer\Package\PackageInterface;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Orisai\Installer\Exception\InvalidConfig;
use Orisai\Installer\Files\NeonReader;
use Orisai\Installer\Schemas\Schema;
use Orisai\Installer\Schemas\Schema_1_0;
use Orisai\Installer\Utils\PathResolver;
use function implode;
use function in_array;
use function sprintf;

final class ConfigValidator
{

	/** @var NeonReader */
	private $reader;

	/** @var PathResolver */
	private $pathResolver;

	public function __construct(NeonReader $reader, PathResolver $pathResolver)
	{
		$this->reader = $reader;
		$this->pathResolver = $pathResolver;
	}

	public function validateConfiguration(PackageInterface $package, string $unresolvedFileName): PackageConfig
	{
		$schemaFileFullName = $this->pathResolver->getSchemaFileFullName($package, $unresolvedFileName);
		$schemaFileRelativeName = $this->pathResolver->getSchemaFileRelativeName($package, $schemaFileFullName);
		$config = $this->reader->read($schemaFileFullName);

		if (!isset($config[PackageConfig::VERSION_OPTION])) {
			throw InvalidConfig::from(
				$package,
				$schemaFileRelativeName,
				sprintf('The mandatory option \'%s\' is missing.', PackageConfig::VERSION_OPTION)
			);
		}

		$version = $config[PackageConfig::VERSION_OPTION];

		if (!in_array($version, Schema::VERSIONS, true)) {
			throw InvalidConfig::from(
				$package,
				$schemaFileRelativeName,
				sprintf(
					'The option \'%s\' expects to be %s, %s given.',
					PackageConfig::VERSION_OPTION,
					implode('|', Schema::VERSIONS),
					$version
				)
			);
		}

		// First version is the only version, no need to handle $version yet
		$schema = new Schema_1_0();
		$structure = $schema->getStructure();

		$processor = new Processor();

		try {
			$config = $processor->process($structure, $config);
		} catch (ValidationException $exception) {
			throw InvalidConfig::from($package, $schemaFileRelativeName, $exception->getMessage());
		}

		return new PackageConfig($config, $package, $schemaFileRelativeName);
	}

}
