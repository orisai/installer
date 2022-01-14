<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

use Composer\Package\PackageInterface;
use Orisai\Installer\Schema\PackageSchema;
use function strrpos;
use function substr;

/**
 * @internal
 */
final class PackageConfig
{

	private string $schemaPath;

	private string $schemaFile;

	private PackageSchema $schema;

	private PackageInterface $package;

	public function __construct(PackageSchema $schema, PackageInterface $package, string $schemaFile)
	{
		$lastSlashPosition = strrpos($schemaFile, '/');
		$this->schemaPath = $lastSlashPosition === false ? '' : substr($schemaFile, 0, $lastSlashPosition);
		$this->schemaFile = $schemaFile;
		$this->schema = $schema;
		$this->package = $package;
	}

	public function getSchemaPath(): string
	{
		return $this->schemaPath;
	}

	public function getSchemaFile(): string
	{
		return $this->schemaFile;
	}

	public function getSchema(): PackageSchema
	{
		return $this->schema;
	}

	public function getPackage(): PackageInterface
	{
		return $this->package;
	}

}
